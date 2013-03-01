<?php
 // Copyright (C) 2005 Rod Roark <rod@sunsetsystems.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

 // This module is used to find and add insurance companies.
 // It is opened as a popup window.  The opener may have a
 // JavaScript function named set_insurance(id, name), in which
 // case selecting or adding an insurance company will cause the
 // function to be called passing the ID and name of that company.

 // When used for searching, this module will in turn open another
 // popup window ins_list.php, which lists the matched results and
 // permits selection of one of them via the same set_insurance()
 // function.

 include_once("../globals.php");
 include_once("$srcdir/acl.inc");
 //needs to be changed.
 require_once("$srcdir/classes/display/TableDataDisplay.php");
 require_once("$srcdir/classes/display/DataDisplayObjectFactory.php");
 
 // Putting a message here will cause a popup window to display it.
 $info_msg = "";


?>
<html>
<head>
<title><?php xl('Secondary Contact','e');?></title>
<link rel="stylesheet" href='<?php  echo $css_header ?>' type='text/css'>

<style>
td { font-size:10pt; }
.search { background-color:#aaffaa }

#form_entry {
	display:block;
}

#form_list {
	display:none;
}

</style>

<script type="text/javascript" src="../../library/topdialog.js"></script>
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/js/jquery.1.3.2.js"></script>

<script language="JavaScript">

 var mypcc = '<?php  echo $GLOBALS['phone_country_code'] ?>';

<?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

 function doescape(value) {
  return encodeURIComponent(value);
 }

 // This is invoked when our Search button is clicked.
 function dosearch() {


	
	$("#form_entry").hide();
  	var f = document.forms[0];
	var person_list = 'person_list.php' +
   '?form_ssn='   + doescape(f.form_ssn.value  ) +
   '&form_gender='   + doescape(f.form_gender.value  ) +
   '&form_relationship='  + doescape(f.form_relationship.value ) +
   '&form_lname='  + doescape(f.form_lname.value ) +
   '&form_fname='   + doescape(f.form_fname.value  );

    top.restoreSession();
    $("#form_list").load( person_list ).show();	

  return false;
 }

 // The ins_list.php window calls this to set the selected insurance.
 function set_insurance(ins_id, ins_name) {
  if (opener.closed || ! opener.set_insurance)
   alert('The target form was closed; I cannot apply your selection.');
  else
   opener.set_insurance(ins_id, ins_name);
   parent.$.fn.fancybox.close();
   parent.location.reload();
   top.restoreSession();
 }

 // This is set to true on a mousedown of the Save button.  The
 // reason is so we can distinguish between clicking on the Save
 // button vs. hitting the Enter key, as we prefer the "default"
 // action to be search and not save.
 var save_clicked = false;

 // Onsubmit handler.
 function validate(f) {
  // If save was not clicked then default to searching.
  if (! save_clicked) return dosearch();
  save_clicked = false;

  msg = '';
  
  //SSN is not required.
  //if (! f.form_ssn.value.length ) msg += 'Company name is missing. ';
  if (! f.form_gender.value.length) msg += 'Gender is missing. ';
  if (! f.form_relationship.value.length ) msg += 'Relationship is missing. ';
  if (! f.form_lname.value.length) msg += 'Last Name is missing. ';
  if (! f.form_fname.value.length  ) msg += 'First is missing.';

  if (msg) {
   alert(msg);
   return false;
  }

  top.restoreSession();
  return true;
 }

</script>

</head>

<body class="body_top" onunload='imclosing()'>
<?php
 // If we are saving, then save and close the window.
 //
 if ($_POST['form_save']) {
  $person_id = '';
  

  if ($ins_id) {
   // sql for updating could go here if this script is enhanced to support
   // editing of existing insurance companies.
  } else {


   //Inserting secondary contact.
   //Create "Secondary person" object.
   //Don't believe it requires update of the person.
   
   //Build person
   //??? Tables to set
  
	$saveObject = DataDisplayObjectFactory::build("PersonTable","person_secondary_contact"); 
	
	$vals = $saveObject->getColumns();
   //Set values
   //Repeat for each value
   $vals["person"]["SSN"] = $_POST['form_ssn'];
   $vals["person"]["gender"] = $_POST['form_gender'];
   
   $vals["person_seconday_contact"]["relationship"] = $_POST['form_relationship'];
   
   
   
   $vals["person_last_name"]["person_last_name"] = $_POST['form_lname'];
   $vals["person_first_name"]["person_first_name"] = $_POST['form_fname'];
   
   $saveObject->save($vals, "secondary_contact");
   
   //Save Value
   
  }

  // Close this window and tell our opener to select the new company.
  //
  echo "<script language='JavaScript'>\n";
  if ($info_msg) echo " alert('$info_msg');\n";
  echo " parent.$.fn.fancybox.close();\n";
  echo " top.restoreSession();\n";
  echo " if (parent.set_insurance) parent.set_insurance($ins_id,'$ins_name');\n";
  echo "</script></body></html>\n";
  exit();
 }

 // Query x12_partners.
 $xres = sqlStatement(
  "SELECT id, name FROM x12_partners ORDER BY name"
 );
?>
<div id="form_entry">

<form method='post' name='theform' action='person_search.php'
 onsubmit='return validate(this)'>
<center>

<p>
<table border='0' width='100%'>

 <!--
 <tr>
  <td valign='top' width='1%' nowrap>&nbsp;</td>
  <td>
   Note: Green fields are searchable.
  </td>
 </tr>
 -->

 <tr>
  <td valign='top' width='1%' nowrap><b><?php xl('Name','e');?>:</b></td>
  <td>
   <input type='text' size='20' name='form_fname' maxlength='35'
    class='search'  title=<?php xl('Name of insurance company','e');?> />
    <input type='text' size='2' name='form_mname' maxlength='35'
    class='search' title=<?php xl('Name of insurance company','e');?> />
    <input type='text' size='30' name='form_lname' maxlength='35'
    class='search' title=<?php xl('Name of insurance company','e');?> />
  </td>
 </tr>

  <tr>
  <td valign='top' width='1%' nowrap><b><?php xl('Gender','e');?>:</b></td>
  <td>
   <select name="form_gender">
	   <option value="Unassigned">Unassigned</option>
	   <option value="Male">Male</option>
	   <option value="Female">Female</option>
   </select>
  </td>
 </tr>
 
 <tr>
  <td valign='top' width='1%' nowrap><b><?php xl('Relationship','e');?>:</b></td>
  <td>
   <select name="form_relationship">
   <option value="Unassigned">Unassigned</option>
	   <option value="Wife">Wife</option>
	   <option value="Husband">Husband</option>
   </select>
  </td>
 </tr>

 <tr>
  <td valign='top' width='1%' nowrap><b><?php xl('SSN','e');?>:</b></td>
  <td>
   <input type='text' size='20' name='form_ssn' maxlength='35'
    class='search' style='width:100%' title=<?php xl('Name of insurance company','e');?> />
  </td>
 </tr>
 
 <!--
 <tr>
  <td valign='top' width='1%' nowrap>&nbsp;</td>
  <td>
   &nbsp;<br><b>Other data:</b>
  </td>
 </tr>
 -->
</table>

<p>&nbsp;<br>
<input type='button' value='<?php xl('Search','e'); ?>' class='search' onclick='dosearch()' />
&nbsp;
<input type='submit' value='<?php xl('Save as New','e'); ?>' name='form_save' onmousedown='save_clicked=true' />
&nbsp;
<input type='button' value='<?php xl('Cancel','e'); ?>' onclick='parent.$.fn.fancybox.close();'/>
</p>

</center>
</form>
</div>

<div id="form_list">
</div>

</body>
</html>
