<?php
# config file for tintiger.net. 
# EDIT THIS FILE CAREFULLY! Any changes will affect how this site works.
# \'//\' and \'#\' designate comment lines. These and blank lines are ignored.
# No spaces are allowed in keywords but are allowed in values and comments. 
# Single quotes in values will mess up ("I'm missing" will only display "I")
#
# domain name
DOMAIN_NAME=tintiger.net
# path from server root (used by image library and HTML upload)
AbsoluteRef=/apache2triad/htdocs/tintiger.net/
# base URL
WhichLocal=http://localhost/tintiger.net
# Home directory for photos from WhichLocal
PhotoDir=photos/
ArticleDir=articles/
# Image that is displayed left side of the header bar
SITE_LOGO=img/tiger_face_outline.gif
LOGO_ALT=ROOAAR!
# Image that is displayed in the browser address bar and user bookmarks
SITE_ICON=/img/tt.jpg
ICON_ALT=tintiger
# Database name, host and access credentials
DB_User=root
DB_Pswd=
DB_Host=localhost
DB_Name=journal
# possible values are 'mysql' or 'sqlite'
DB_Type=mysql
TB_Prefix=
# Username(s) that can add journal entries automatically.
jnlEditor=kknerr
# username(s) that have complete access to any Admin function
SiteAdmin=kknerr,someone,janice142
# Administrator email
SMTP_SERVER=smtp.tintiger.net
ADMIN_EMAIL=webmaster@tintiger.net
# mark thing deleted and keep=0, get rid of the thing entirely=1
DELETE_NO_SAVE=0
# 1 to allow visitors to create accounts, 0 to disallow
ALLOW_REGISTRATION=1
# 1 to Hide comments until moderated Show, 0 to show all comments not Hidden or Deleted
MODERATE_COMMENTS=0
MAX_USERNAME_LENGTH=16
MAX_PASSWORD_LENGTH=10
# Alternate colors for display lists.
BAR_COLOR_1=#7FFFD4
BAR_COLOR_2=#FAEBD7
# contact us form 
cuEmail=spam@tintiger.net
cuMessage="Shank you. We will respond as soon as we sober up."

# just a place holder...
END=end
?>
