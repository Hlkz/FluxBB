<?php

// Language definitions for frequently used strings
$lang_common = array(

// Text orientation and encoding
'lang_direction'					=>	'ltr', // ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_identifier'					=>	'en',

// Number formatting
'lang_decimal_point'				=>	'.',
'lang_thousands_sep'				=>	',',

// Menu
'Nav Index'							=>	'Index',
'Nav Game'							=>	'Game',
'Nav News'							=>	'News',
'Nav Board'							=>	'Board',
'Nav Database'						=>	'Database',
'Nav Account'						=>	'Account',
'Nav Language'						=>	'Language',
'Login'								=>	'Login',
'Logout'							=>	'Logout',
'Signin'							=>	'Register', // Page register
'Register'							=>	'Register', // Button register
'Manage account'					=>	'Manage account',
'Not logged in'						=>	'You are not logged in.',
'Logged in as'						=>	'Logged in as',
'Remember me'						=>	'Remember me',
'Forgotten pass'					=>	'Forgotten pass',
'Search'							=>	'Search',

// Board

'Board'								=>	'Board',
'Forum'								=>	'Forum',
'Topic'								=>	'Topic',
'Topics'							=>	'Topics',
'Submit'							=>	'Submit',
'Post'								=>	'Post',
//'Quick post'						=>	'Quick post',	UNUSED
'Author'							=>	'Author',
'Author:'							=>	'Author:',
'Forum closed'						=>	'Forum closed',
'Topic closed'						=>	'Topic closed',
'Delete info'						=>	'The post you have chosen to delete is set out below for you to review before proceeding.',
'Delete post warning'				=>	'You are about to permanently delete this post.',
'Delete topic warning'				=>	'Warning! This is the first post in the topic, the whole topic will be permanently deleted.',
'Board empty'						=>	'Board vide.',
'Forum empty'						=>	'Forum vide.',

'Post message'						=>	'Post',
'Post reply'						=>	'Reply',
'Post topic'						=>	'New thread',
'Quote'								=>	'Quote',
'Edit'								=>	'Edit',
'Edit post'							=>	'Edit post',
'Preview'							=>	'Preview',
'Delete'							=>	'Delete',
'Delete post'						=>	'Delete post',
'Delete topic'						=>	'Delete topic',
'Delete post confirm'				=>	'Confirm deletion',
//'Report'							=>	'Report',	UNUSED

'Cat Realm'							=>	'Aviana Realm',

// Post validation stuff (many are similiar to those in edit.php)
'No subject'		=>	'Topics must contain a subject.',
'No subject after censoring'	=>	'Topics must contain a subject. After applying censoring filters, your subject was empty.',
'Too long subject'	=>	'Subjects cannot be longer than 70 characters.',
'No message'		=>	'You must enter a message.',
'No message after censoring'	=>	'You must enter a message. After applying censoring filters, your message was empty.',
'Too long message'	=>	'Posts cannot be longer than %s bytes.',
'All caps subject'	=>	'Subjects cannot contain only capital letters.',
'All caps message'	=>	'Posts cannot contain only capital letters.',
'Empty after strip'	=>	'It seems your post consisted of empty BBCodes only. It is possible that this happened because e.g. the innermost quote was discarded because of the maximum quote depth level.',

// Posting
'Post errors'		=>	'Post errors',
'Post errors info'	=>	'The following errors need to be corrected before the message can be posted:',
'Post preview'		=>	'Post preview',
'Post redirect'		=>	'Post entered. Redirecting …',
'Post a reply'		=>	'Post a reply',
'Post new topic'	=>	'Post new topic',
'Hide smilies'		=>	'Never show smilies as icons for this post',
'Subscribe'			=>	'Subscribe to this topic',
'Stay subscribed'	=>	'Stay subscribed to this topic',
'Topic review'		=>	'Topic review (newest first)',
'Flood start'		=>	'At least %s seconds have to pass between posts. Please wait %s seconds and try posting again.',

// Edit post
'Edit post legend'	=>	'Edit the post and submit changes',
'Silent edit'		=>	'Silent edit (don\'t display "Edited by ..." in topic view)',
'Edit redirect'		=>	'Post updated. Redirecting …',

// User
'Account'							=>	'Account',
'Username'							=>	'Username',
'Password'							=>	'Password',
'Password2'							=>	'Confirm',
'Email'								=>	'Email',
'ToolTip Account'					=>	'Name used to log you in.',
'ToolTip Username'					=>	'Showed to the world. Can differ from your Account name.',
'ToolTip Password'					=>	'Password used to log you in.',
'ToolTip Password2'					=>	'Confirm your password.',
'ToolTip Email'						=>	'Used to retrieve your password or sending important informations.',
'Wrong user/pass'					=>	'There are no account matching your informations.',

// Notices
'Bad request'						=>	'Bad request. The link you followed is incorrect or outdated.',
'No view'							=>	'You do not have permission to view these forums.',
'No permission'						=>	'You do not have permission to access this page.',
'Bad referrer'						=>	'Bad HTTP_REFERER. You were referred to this page from an unauthorized source. If the problem persists please make sure that \'Base URL\' is correctly set in Admin/Options and that you are visiting the forum by navigating to that URL. More information regarding the referrer check can be found in the FluxBB documentation.',
'No cookie'							=>	'You appear to have logged in successfully, however a cookie has not been set. Please check your settings and if applicable, enable cookies for this website.',
'Pun include extension'  			=>	'Unable to process user include %s from template %s. "%s" files are not allowed',
'Pun include directory'				=>	'Unable to process user include %s from template %s. Directory traversal is not allowed',
'Pun include error'					=>	'Unable to process user include %s from template %s. There is no such file in neither the template directory nor in the user include directory',

// Miscellaneous
'Announcement'						=>	'Announcement',
'Options'							=>	'Options',
'Never'								=>	'Never',
'Today'								=>	'Today',
'Yesterday'							=>	'Yesterday',
'Info'								=>	'Info', // A common table header
'Go back'							=>	'Go back',
'Maintenance'						=>	'Maintenance',
'Redirecting'						=>	'Redirecting',
'Click redirect'					=>	'Click here if you do not want to wait any longer (or if your browser does not automatically forward you)',
'on'								=>	'on', // As in "BBCode is on"
'off'								=>	'off',
'Invalid email'						=>	'The email address you entered is invalid.',
'Required'							=>	'(Required)',
'required field'					=>	'is a required field in this form.', // For javascript form validation
'Last post'							=>	'Last post',
'by'								=>	'by', // As in last post by some user
'New posts'							=>	'New posts', // The link that leads to the first new post
'New posts info'					=>	'Go to the first new post in this topic.', // The popup text for new posts links
'Send email'						=>	'Send email',
'Moderated by'						=>	'Moderated by',
'Registered'						=>	'Registered',
'Subject'							=>	'Subject',
'Message'							=>	'Message',
'Topic'								=>	'Topic',
'Forum'								=>	'Forum',
'Posts'								=>	'Posts',
'Replies'							=>	'Replies',
'Pages'								=>	'Pages:',
'Page'								=>	'Page %s',
'BBCode'							=>	'BBCode:', // You probably shouldn't change this
'url tag'							=>	'[url] tag:',
'img tag'							=>	'[img] tag:',
'Smilies'							=>	'Smilies:',
'and'								=>	'and',
'Image link'						=>	'image', // This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'								=>	'wrote:', // For [quote]'s
'Mailer'							=>	'%s Mailer', // As in "MyForums Mailer" in the signature of outgoing emails
'Important information'				=>	'Important information',
'Write message legend'				=>	'Write your message and submit',
'Previous'							=>	'Previous',
'Next'								=>	'Next',
'Spacer'							=>	'…', // Ellipsis for paginate

// Title
'Title'								=>	'Title',
'Member'							=>	'Member', // Default title
'Moderator'							=>	'Moderator',
'Administrator'						=>	'Administrator',
'Banned'							=>	'Banned',
'Guest'								=>	'Guest',

// Stuff for include/parser.php
'BBCode error no opening tag'		=>	'[/%1$s] was found without a matching [%1$s]',
'BBCode error invalid nesting'		=>	'[%1$s] was opened within [%2$s], this is not allowed',
'BBCode error invalid self-nesting'	=>	'[%s] was opened within itself, this is not allowed',
'BBCode error no closing tag'		=>	'[%1$s] was found without a matching [/%1$s]',
'BBCode error empty attribute'		=>	'[%s] tag had an empty attribute section',
'BBCode error tag not allowed'		=>	'You are not allowed to use [%s] tags',
'BBCode error tag url not allowed'	=>	'You are not allowed to post links',
'BBCode code problem'				=>	'There is a problem with your [code] tags',
'BBCode list size error'			=>	'Your list was too long to parse, please make it smaller!',

'Last visit'						=>	'Last visit: %s',
'Mark all as read'					=>	'Mark all topics as read',
'Mark forum read'					=>	'Mark this forum as read',
'Title separator'					=>	' / ',

// Stuff for the page footer
'Board footer'						=>	'Board footer',
'All'								=>	'All',
'Move topic'						=>	'Move topic',
'Open topic'						=>	'Open topic',
'Close topic'						=>	'Close topic',
'Unstick topic'						=>	'Unstick topic',
'Stick topic'						=>	'Stick topic',

// Debug information
'Debug table'						=>	'Debug information',
'Querytime'							=>	'Generated in %1$s seconds, %2$s queries executed',
'Memory usage'						=>	'Memory usage: %1$s',
'Peak usage'						=>	'(Peak: %1$s)',
'Query times'						=>	'Time (s)',
'Query'								=>	'Query',
'Total query time'					=>	'Total query time: %s',

// Units for file sizes
'Size unit B'						=>	'%s B',
'Size unit KiB'						=>	'%s KiB',
'Size unit MiB'						=>	'%s MiB',
'Size unit GiB'						=>	'%s GiB',
'Size unit TiB'						=>	'%s TiB',
'Size unit PiB'						=>	'%s PiB',
'Size unit EiB'						=>	'%s EiB',

);
