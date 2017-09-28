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
The plugin can inform your students as soon as new material is published in one of their courses. The delivered mail can optionally include the new file. These functions must be enabled by three parties: The moodle admin, the teacher and the student. The options for courses and for students are only visible if the admin has enabled the feature. 

<img src="https://user-images.githubusercontent.com/9339300/30982489-d3f37404-a487-11e7-9dab-bf843fd35bd4.png" width="400">

As a **moodle admin** follow these steps
1. Go to `Site administration` -> `Plugins` -> `Local plugins` -> `Upload notification`
2. Set `Allow notification mail delivery` to yes. This will enable the mail delivery for course admins and students. If you set it to no, the delivery is completely disabled: No one can see the settings and no mails will be send in any case.
3. Set `Maximum filesize of mail attachments (in KB)` to 100000. This will allow attachments up to 10MB. If this value is zero, no attachments will be send.

Now the feature is visible for teachers and students. As a general rule: A mail will only be send if someone (teacher or student) has requested the delivery and no one has forbidden it.

<img src="https://user-images.githubusercontent.com/9339300/30982447-b9cf8c66-a487-11e7-9914-c118a0dd61fc.png" width="400">

As a **teacher** follow these steps
1. Go to your course -> Open the course menu (where you can access the settings, turn editing on, ...) -> Click on the link `Uploadnotification`
2. You have three options for `Enable notification mail delivery for material uploads`
   -  `No preferences` The default option. No mails will be send except the user requests them.
   - `Disable` No mails will be send for actions in this course. The student settings are ignored.
   - `Enable` Mails will be send except a user has disabled the delivery for himself.  
3. Set the check mark for `Allow email attachments` (this is the default). This will allow students to receive mails with attachments. As a teacher you can not force this to avoid unwanted traffic.

<img src="https://user-images.githubusercontent.com/9339300/30982503-e20521fa-a487-11e7-977b-1ba39c92d37f.png" width="400">

As a **student** follow these steps
1. Click on your username and select `Preferences`
2. At `Miscellaneous` you find a link `Uploadnotification`
3. All students have two settings:
   - `Enable notification mail delivery for material uploads` with the same options as teachers. `No preference` will only send mails if the course settings have enabled them. `Enable` sends always mails except a teacher has disabled the feature for a course. `Disable` will never allow the mail delivery to this student.
   - `Maximum filesize of mail attachments (in KB)` Insert a value greater zero to receive attachments up to this size. You can not request sizes which are greater than the limit of the moodle admin. The attachment will only be send if the teacher has not removed the check mark in his his course.

<img src="https://user-images.githubusercontent.com/9339300/30982471-c7a63fba-a487-11e7-90b0-54e6a76b480e.png" width="400">

###  Changelog generation
The plugin can build a changelog for uploaded resources. It will include the timestamp of the update and the filename of the predecessor. At the moment only file resources are supported (no folders, pages, or others). For PDF documents, the plugin can detect the page number on which changes were performed. 

Teachers can update material on two ways:
1. Delete the old resource and upload the new document independently
2. Edit the current resource and select a new file.

A file and its predecessor must be uploaded in the same course and section.

<img src="https://user-images.githubusercontent.com/9339300/30982463-c4d12a20-a487-11e7-93b8-3140a8aa2c59.png" width="400">

To enable the changelog, the **moodle admin** has to allow this feature. If he has deactivated it, teachers will not see the options in the course settings.
1. Go to `Site administration` -> `Plugins` -> `Local plugins` -> `Upload notification`
2. Set `Allow automatic changelog generation in courses` on true. This will activate the feature for teachers.
3. Set `Allowed size of analysed files for the diff detection (in MB)` to 100. This will allow the plugin to search for differences on documents with maximum 100MB filesize. If you set this value to zero, a changelog can be generated, but the pages where changes are performed will not longer be identified.
4. Set `Enable the changelog in new courses by default` and `Enable the diff detection in new courses by default` to true. This will activate the changelog and diff detection in all courses which have no custom settings.

As a **teacher** follow these steps to customize the behaviour in your course:
1. Go to your course -> Open the course menu (where you can access the settings, turn editing on, ...) -> Click on the link `Uploadnotification`
2. Set `Display a changelog for updates` to true. This will print an information with the filename of the predecessor and the time of the change next to the new document.
3. Set `Detect differences in updates` to true. This will search for changed pages in the new document and add their numbers to the changelog. This feature only works for PDF documents which are smaller than the admin-limit.
