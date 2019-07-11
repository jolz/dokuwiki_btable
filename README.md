# dokuwiki_btable2
doodle-like polls as plugin for dokuwiki. dokuwiki-page: https://www.dokuwiki.org/plugin:btable2

successor of [btable](https://www.dokuwiki.org/plugin:btable) by Oliver Horst.

btable2 is a drop-in-replacement. uninstall btable, install btable2. All old polls will stay as they were.

# Usage

Use for polls in internal wiki with trustfull members. I use it in my Band to check, if we can accept a gig request. 

- Very simple (one you have your copy&Past template, its easy. especially for the others.)
- No Authorization! Everyone can change votes from others. non-internet-users (yes, they exist...) can be updated by their friends. 

# Example
![screenshot1](https://raw.githubusercontent.com/jolz/dokuwiki_btable/master/doc/screenshot1.png)

is created with this code (and some votes):
```

<btable "2019:06:rehersal">
<opt>showempty,colongroups</opt>
  <columns>
5.
12.
19.
26.  
  </columns>
  <rows>
Singer: one
Singer: two
Singer: three
Band: Guitar 1
Band: Guitar 2
Band: Drums
Band: Keys
Band: Bass
  </rows>
</btable>
```

# Documentation
- tag "opt" - list of options, seperated by whitespace or comma 
  - showempty (will include rows without voted in table)
  - colongroups (if colon found in name - use it as group)
  - close (disable frontend changes.)
 - tag "columns"
   - column names seperated by "^" or "\n" (newline)
 - tag "rows"
   - row names seperated by "^" or "\n" (newline)

# Added Features since btable2
- Version 2019-06-22 (first release)
  - php7 compatible
  - compatible to newer dokuwiki
  - implement options "closed", "colongroups", "showempty"
  - rows/columns seperator may also be newline instead of "^". 
  - implement dokuwiki security token (dokuwiki security guideline)
