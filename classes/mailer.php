<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Digest mailer.
 */
class local_tdmmodnotify_mailer {
    /**
     * Array/iterator of recipients.
     *
     * @var \local_tdmmodnotify_recipient[]|\local_tdmmodnotify_recipient_iterator
     */
    protected $recipients;

    /**
     * Support user record.
     *
     * @var stdClass
     */
    protected $supportuser;

    /**
     * Initialiser.
     *
     * @param \local_tdmmodnotify_recipient[]|\local_tdmmodnotify_recipient_iterator $recipients  Array/iterator of
     *                                                                                            recipients.
     * @param stdClass                                                               $supportuser Support user record.
     */
    public function __construct($recipients, $supportuser) {
        $this->recipients  = $recipients;
        $this->supportuser = $supportuser;
    }

    /**
     * Delete scheduled notifications for a recipient.
     *
     * @param \local_tdmmodnotify_recipient $recipient The recipient record.
     *
     * @return void
     */
    public function delete_scheduled_notifications($recipient) {
        $recipient->delete();
    }

    /**
     * Execute the mailer.
     *
     * Send all of the scheduled notifications in digest form.
     *
     * @return void
     */
    public function execute() {
        foreach ($this->recipients as $recipient) {
            mtrace("user#{$recipient->userid}");

            $this->mail($recipient);
            $this->delete_scheduled_notifications($recipient);
        }
    }

    /**
     * Mail a single recipient.
     *
     * @param \local_tdmmodnotify_recipient $recipient The recipient record.
     *
     * @return void
     */
    protected function mail($recipient) {
        $user = core_user::get_user($recipient->userid);
        $user->mailformat = 1;

        $url = new moodle_url('/course/view.php');

        $substitutions = (object) array(
            'firstname' => $recipient->userfirstname,
            'signoff'   => generate_email_signoff(),
            'baseurl'   => $url,
        );
        $substitutions->notifications = $recipient->build_content($substitutions);

        $subject = get_string('templatesubject', 'local_tdmmodnotify');

        $message     = get_string('templatemessage', 'local_tdmmodnotify', $substitutions);
        $messagehtml = text_to_html($message, false, false, true);

        email_to_user($user, $this->supportuser, $subject, $message, $messagehtml);
    }
}
