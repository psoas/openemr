<?php 
 // Copyright (C) 2005 Rod Roark <rod@sunsetsystems.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

 // The purpose of this module is to show a list of insurance
 // companies that match the passed-in search strings, and to allow
 // one of them to be selected.

 include_once("../globals.php");
 require_once("$srcdir/classes/display/DataDisplayObjectFactory.php");
 // Putting a message here will cause a popup window to display it.
 $info_msg = "";

 // The following code builds the appropriate SQL query from the
 // search parameters passed by our opener (ins_search.php).

 
  //'?form_ssn='   + doescape(f.form_ssn.value  ) +
  //'&form_gender='   + doescape(f.form_gender.value  ) +
  //'&form_relationship='  + doescape(f.form_relationship.value ) +
  //'&form_lname='  + doescape(f.form_lname.value ) +
  //'&form_fname='   + doescape(f.form_fname.value  );
  //Include person object and do search.
 
   //Create Person Table object do search.
 $person = DataDisplayObjectFactory::build("PersonTable");
 
  $res = $person->search(23,"secondary_contact");
  
?>
<html>
<head>
<title><?php xl('List People','e');?></title>
<link rel="stylesheet" href='<?php  echo $css_header ?>' type='text/css'>

<style>
td { font-size:10pt; }
</style>

<script language="JavaScript">

 // This is invoked when an insurance company name is clicked.
 function setperson(person_id, person_name) {
   parent.set_person(person_id, person_name);
   parent.$.fn.fancybox.close();
   return false;
 }

</script>

</head>

<body class="body_top">
<form method='post' name='theform'>
<center>

<table border='0' width='100%'>
 <tr>
  <td><b><?php xl('Name','e');?></b>&nbsp;</td>
  <td><b><?php xl('SSN','e');?></b>&nbsp;</td>
 </tr>

<?php 
  while ($row = sqlFetchArray($res)) {
   $anchor = "<a href=\"\" onclick=\"return setperson(" .
    $row['person_id'] . ",'" . addslashes($row['last_name'].", ".$row['first_name']) . "')\">";
   echo " <tr>\n";
   echo "  <td valign='top'>$anchor" . $row['person_last_name'].", ".$row['person_first_name'] . "</a>&nbsp;</td>\n";
   echo "  <td valign='top'>" . $row['social_security_number'] . "&nbsp;</td>\n";
   echo " </tr>\n";
  }
?>
</table>

</center>
</form>
</body>
</html>
