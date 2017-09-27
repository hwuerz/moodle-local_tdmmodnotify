Upload notification
=====================================================

This plugins allows students to receive a notification as soon as new material becomes uploaded. 
Optionally the new file can be included in the mail directly.
For updated material a changelog will be displayed which includes the changed page for PDF documents.

License
-------

    Copyright (c) The Development Manager Ltd, Hendrik Wuerz

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

Requirements
------------

* Tested with Moodle 3.3+
* Requires the plugin `local_changeloglib`

Installation
--------

1. cd to `MOODLE_HOME`
2. Clone the repo inside MOODLE_HOME/local/uploadnotification
   ```bash
   git clone git@github.com:hwuerz/moodle-local_uploadnotification.git local/uploadnotification
   ```
3. Browse to Site Administration -> Notifications and allow the database upgrades to execute
4. After installation define your admin settings to customize the plugin behaviour.

Tests
------

This plugin provides tests for the main features. To run them please follow the next steps:

1. Install PHPUnit on your system and configure moodle. See [https://docs.moodle.org/dev/PHPUnit](https://docs.moodle.org/dev/PHPUnit) for more information.
2. Install the plugin.
3. Run the tests
    ```bash
    cd /path/to/moodle/home
    php admin/tool/phpunit/cli/init.php
    vendor/bin/phpunit --group local_uploadnotification
    ``` 

Features
--------

### Mail delivery for new material
The plugin can inform your students as soon as new material is published in one of their courses. The delivered mail can optionally include the new file. These functions must be enabled by three parties: The moodle admin, the course admin and the student. The options for courses and for students are only visible if the admin has enabled the feature. 

As an moodle admin follow these steps
1. Open moodle
2. Go to `Site administration` -> `Plugins` -> `Local plugins` -> `Upload notification`
3. Set `Allow notification mail delivery` to yes. This will enable the mail delivery for course admins and students. If you set it to no, the delivery is completely disabled: No one can see the settings and no mails will be send in any case.
4. Set `Maximum filesize of mail attachments (in KB)` to 100000. This will allow attachments up to 10MB. If this value is zero, no attachments will be send.

Now the feature is visible for course admins and students. As a general rule: A mail will only be send if someone (teacher or student) has requested the delivery and no one has forbidden it.

As a teacher follow these steps
1. Open moodle
2. Go to your course -> Open the course menu (where you can access the settings, turn editing on, ...) -> Click on the link `Uploadnotification`
3. You have three options for `Enable notification mail delivery for material uploads`
- `No preferences` The default option. No mails will be send except the user requests them.
- `Disable` No mails will be send for actions in this course. The student settings are ignored.
- `Enable` Mails will be send except a user has disabled the delivery for himself.  
4. Set the check mark for `Allow email attachments` (this is the default). This will allow students to receive mails with attachments. As a teacher you can not force this to avoid unwanted traffic.

As a student follow these steps
1. Open moodle
2. Click on your username and select `Preferences`
3. At `Miscellaneous` you find a link `Uploadnotification`
4. All students have two settings:
- `Enable notification mail delivery for material uploads` with the same options as teachers. `No preference` will only send mails if the course settings have enabled them. `Enable` sends always mails except a teacher has disabled the feature for a course. `Disable` will never allow the mail delivery to this student.
- `Maximum filesize of mail attachments (in KB)` Insert a value greater zero to receive attachments up to this size. You can not request sizes which are greater than the limit of the moodle admin. The attachment will only be send if the teacher has not removed the check mark in his his course.
