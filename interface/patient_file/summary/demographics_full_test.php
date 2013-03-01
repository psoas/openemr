<?php
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

require_once("../../globals.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/options_test.inc.php");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/erx_javascript.inc.php");

require_once("$srcdir/classes/display/tableLookup.php");

require_once("$srcdir/classes/display/ContactAddressTable.php");
require_once("$srcdir/classes/display/DataDisplayObjectFactory.php");

require_once("$srcdir/classes/display/SubItem.php");



 // Session pid must be right or bad things can happen when demographics are saved!
 //
 include_once("$srcdir/pid.inc");
 $set_pid = $_GET["set_pid"] ? $_GET["set_pid"] : $_GET["pid"];
 if ($set_pid && $set_pid != $_SESSION["pid"]) {
  setpid($set_pid);
 }

 include_once("$srcdir/patient.inc");

 $result = getPatientData($pid, "*, DATE_FORMAT(DOB,'%Y-%m-%d') as DOB_YMD");
 $result2 = getEmployerData($pid);

 // Check authorization.
 $thisauth = acl_check('patients', 'demo');
 if ($pid) {
  if ($thisauth != 'write')
   die(xl('Updating demographics is not authorized.'));
  if ($result['squad'] && ! acl_check('squads', $result['squad']))
   die(xl('You are not authorized to access this squad.'));
 } else {
  if ($thisauth != 'write' && $thisauth != 'addonly')
   die(xl('Adding demographics is not authorized.'));
 }

$CPR = 4; // cells per row

// $statii = array('married','single','divorced','widowed','separated','domestic partner');
// $langi = getLanguages();
// $ethnoraciali = getEthnoRacials();
// $provideri = getProviderInfo();

$insurancei = getInsuranceProviders();

$fres = sqlStatement("SELECT * FROM layout_options " .
  "WHERE form_id = 'DEM' AND uor > 0 " .
  "ORDER BY group_name, seq");
?>
<html>
<head>
<?php html_header_show();?>

<link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">

<style type="text/css">@import url(../../../library/dynarch_calendar.css);</style>

<script type="text/javascript" src="../../mvc/person.php"></script>
<script src="../../../library/js/jquery-1.8.3.min.js"></script>
<link href="../../../library/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
<script src="../../../library/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="../../../library/js/fancybox/jquery.fancybox-1.2.6.js"></script>
<script type="text/javascript" src="../../../library/js/knockout-2.2.0.js"></script>
<link rel="stylesheet" type="text/css" href="../../../library/js/fancybox/jquery.fancybox-1.2.6.css" media="screen" />
<script type="text/javascript" src="../../../library/js/underscore-1.4.3.js"></script>
<script type="text/javascript" src="../../../library/js/knockout.mapping-latest.debug-2.3.5.js"></script>


<script type="text/javascript" src="../../../library/dialog.js"></script>
<script type="text/javascript" src="../../../library/textformat.js"></script>
<script type="text/javascript" src="../../../library/dynarch_calendar.js"></script>
<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
<script type="text/javascript" src="../../../library/dynarch_calendar_setup.js"></script>

<!-- <script type="text/javascript" src="../../../library/js/jquery.1.3.2.js"></script> -->
<script type="text/javascript" src="../../../library/js/common.js"></script>


<script type="text/javascript">


$(document).ready(function(){
	

	tabbify();
    enable_modals();

    // special size for
	$(".medium_modal").fancybox( {
		'overlayOpacity' : 0.0,
		'showCloseButton' : true,
		'frameHeight' : 460,
		'frameWidth' : 650
	});

});

var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

//code used from http://tech.irt.org/articles/js037/
function replace(string,text,by) {
 // Replaces text with by in string
 var strLength = string.length, txtLength = text.length;
 if ((strLength == 0) || (txtLength == 0)) return string;

 var i = string.indexOf(text);
 if ((!i) && (text != string.substring(0,txtLength))) return string;
 if (i == -1) return string;

 var newstr = string.substring(0,i) + by;

 if (i+txtLength < strLength)
  newstr += replace(string.substring(i+txtLength,strLength),text,by);

 return newstr;
}

function upperFirst(string,text) {
 return replace(string,text,text.charAt(0).toUpperCase() + text.substring(1,text.length));
}

<?php for ($i=1;$i<=3;$i++) { ?>
function auto_populate_employer_address<?php echo $i ?>(){
 var f = document.demographics_form;
 if (f.form_i<?php echo $i?>subscriber_relationship.options[f.form_i<?php echo $i?>subscriber_relationship.selectedIndex].value == "self")
 {
  f.i<?php echo $i?>subscriber_fname.value=f.form_fname.value;
  f.i<?php echo $i?>subscriber_mname.value=f.form_mname.value;
  f.i<?php echo $i?>subscriber_lname.value=f.form_lname.value;
  f.i<?php echo $i?>subscriber_street.value=f.form_street.value;
  f.i<?php echo $i?>subscriber_city.value=f.form_city.value;
  f.form_i<?php echo $i?>subscriber_state.value=f.form_state.value;
  f.i<?php echo $i?>subscriber_postal_code.value=f.form_postal_code.value;
  if (f.form_country_code)
    f.form_i<?php echo $i?>subscriber_country.value=f.form_country_code.value;
  f.i<?php echo $i?>subscriber_phone.value=f.form_phone_home.value;
  f.i<?php echo $i?>subscriber_DOB.value=f.form_DOB.value;
  f.i<?php echo $i?>subscriber_ss.value=f.form_ss.value;
  f.form_i<?php echo $i?>subscriber_sex.value = f.form_sex.value;
  f.i<?php echo $i?>subscriber_employer.value=f.form_em_name.value;
  f.i<?php echo $i?>subscriber_employer_street.value=f.form_em_street.value;
  f.i<?php echo $i?>subscriber_employer_city.value=f.form_em_city.value;
  f.form_i<?php echo $i?>subscriber_employer_state.value=f.form_em_state.value;
  f.i<?php echo $i?>subscriber_employer_postal_code.value=f.form_em_postal_code.value;
  if (f.form_em_country)
    f.form_i<?php echo $i?>subscriber_employer_country.value=f.form_em_country.value;
 }
}

<?php } ?>

function popUp(URL) {
 day = new Date();
 id = day.getTime();
 top.restoreSession();
 eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=400,height=300,left = 440,top = 362');");
}

function checkNum () {
 var re= new RegExp();
 re = /^\d*\.?\d*$/;
 str=document.demographics_form.monthly_income.value;
 if(re.exec(str))
 {
 }else{
  alert("<?php xl('Please enter a monetary amount using only numbers and a decimal point.','e'); ?>");
 }
}

//Added for person search.
// Indicates which insurance slot is being updated.
var person_index = 0;

// The OnClick handler for searching/adding the insurance company.
function person_search(per) {
	person_index = per;
	return false;
}

// The ins_search.php window calls this to set the selected insurance.
function set_person(per_id, per_name) {
 //TODO: Update values.
 var thesel = document.forms[0]['i' + insurance_index + 'provider'];
 var theopts = thesel.options; // the array of Option objects
 var i = 0;
 for (; i < theopts.length; ++i) {
  if (theopts[i].value == ins_id) {
   theopts[i].selected = true;
   return;
  }
 }
 // no matching option was found so create one, append it to the
 // end of the list, and select it.
 theopts[i] = new Option(ins_name, ins_id, false, true);
}



// Indicates which insurance slot is being updated.
var insurance_index = 0;

// The OnClick handler for searching/adding the insurance company.
function ins_search(ins) {
	insurance_index = ins;
	return false;
}

// The ins_search.php window calls this to set the selected insurance.
function set_insurance(ins_id, ins_name) {
 var thesel = document.forms[0]['i' + insurance_index + 'provider'];
 var theopts = thesel.options; // the array of Option objects
 var i = 0;
 for (; i < theopts.length; ++i) {
  if (theopts[i].value == ins_id) {
   theopts[i].selected = true;
   return;
  }
 }
 // no matching option was found so create one, append it to the
 // end of the list, and select it.
 theopts[i] = new Option(ins_name, ins_id, false, true);
}

// This capitalizes the first letter of each word in the passed input
// element.  It also strips out extraneous spaces.
function capitalizeMe(elem) {
 var a = elem.value.split(' ');
 var s = '';
 for(var i = 0; i < a.length; ++i) {
  if (a[i].length > 0) {
   if (s.length > 0) s += ' ';
   s += a[i].charAt(0).toUpperCase() + a[i].substring(1);
  }
 }
 elem.value = s;
}

function divclick(cb, divid) {
 var divstyle = document.getElementById(divid).style;
 if (cb.checked) {
  divstyle.display = 'block';
 } else {
  divstyle.display = 'none';
 }
 return true;
}

// Compute the length of a string without leading and trailing spaces.
function trimlen(s) {
 var i = 0;
 var j = s.length - 1;
 for (; i <= j && s.charAt(i) == ' '; ++i);
 for (; i <= j && s.charAt(j) == ' '; --j);
 if (i > j) return 0;
 return j + 1 - i;
}

function validate(f) {
 var errCount = 0;
 var errMsgs = new Array();
<?php generate_layout_validation('DEM'); ?>

 var msg = "";
 msg += "<?php xl('The following fields are required', 'e' ); ?>:\n\n";
 for ( var i = 0; i < errMsgs.length; i++ ) {
	msg += errMsgs[i] + "\n";
 }
 msg += "\n<?php xl('Please fill them in before continuing.', 'e'); ?>";

 if ( errMsgs.length > 0 ) {
	alert(msg);
 }
 
//Patient Data validations
 <?php if($GLOBALS['erx_enable']){ ?>
 alertMsg='';
 for(i=0;i<f.length;i++){
  if(f[i].type=='text' && f[i].value)
  {
   if(f[i].name == 'form_fname' || f[i].name == 'form_mname' || f[i].name == 'form_lname')
   {
    alertMsg += checkLength(f[i].name,f[i].value,35);
    alertMsg += checkUsername(f[i].name,f[i].value);
   }
   else if(f[i].name == 'form_street' || f[i].name == 'form_city')
   {
    alertMsg += checkLength(f[i].name,f[i].value,35);
    alertMsg += checkAlphaNumericExtended(f[i].name,f[i].value);
   }
   else if(f[i].name == 'form_phone_home')
   {
    alertMsg += checkPhone(f[i].name,f[i].value);
   }
  }
 }
 if(alertMsg)
 {
   alert(alertMsg);
   return false;
 }
 <?php } ?>
 //return false;
 
// Some insurance validation.
 for (var i = 1; i <= 3; ++i) {
  subprov = 'i' + i + 'provider';
  if (!f[subprov] || f[subprov].selectedIndex <= 0) continue;
  var subpfx = 'i' + i + 'subscriber_';
  var subrelat = f['form_' + subpfx + 'relationship'];
  var samename =
   f[subpfx + 'fname'].value == f.form_fname.value &&
   f[subpfx + 'mname'].value == f.form_mname.value &&
   f[subpfx + 'lname'].value == f.form_lname.value;
  var samess = f[subpfx + 'ss'].value == f.form_ss.value;
  if (subrelat.options[subrelat.selectedIndex].value == "self") {
   if (!samename) {
    if (!confirm("<?php xl('Subscriber relationship is self but name is different! Is this really OK?','e'); ?>"))
     return false;
   }
   if (!samess) {
    alert("<?php xl('Subscriber relationship is self but SS number is different!','e'); ?>");
    return false;
   }
  } // end self
  else {
   if (samename) {
    if (!confirm("<?php xl('Subscriber relationship is not self but name is the same! Is this really OK?','e'); ?>"))
     return false;
   }
   if (samess) {
    alert("<?php xl('Subscriber relationship is not self but SS number is the same!','e'); ?>");
    return false;
   }
  } // end not self
 } // end for

 return errMsgs.length < 1;
}

function submitme() {
 var f = document.forms[0];
 if (validate(f)) {
  top.restoreSession();
  f.submit();
 }
}

// Onkeyup handler for policy number.  Allows only A-Z and 0-9.
function policykeyup(e) {
 var v = e.value.toUpperCase();
 for (var i = 0; i < v.length; ++i) {
  var c = v.charAt(i);
  if (c >= '0' && c <= '9') continue;
  if (c >= 'A' && c <= 'Z') continue;
  if (c == '*') continue;
  if (c == '-') continue;
  if (c == '_') continue;
  if (c == '(') continue;
  if (c == ')') continue;
  if (c == '#') continue;
  v = v.substring(0, i) + v.substring(i + i);
  --i;
 }
 e.value = v;
 return;
}

// Added 06/2009 by BM to make compatible with list_options table and functions - using jquery
$(document).ready(function() {

 <?php for ($i=1;$i<=3;$i++) { ?>
  $("#form_i<?php echo $i?>subscriber_relationship").change(function() { auto_populate_employer_address<?php echo $i?>(); });
 <?php } ?>

});

</script>

 
</head>

<body class="body_top">
<form action='demographics_save.php' name='demographics_form' method='post' onsubmit='return validate(this)'>
<input type='hidden' name='mode' value='save' />
<input type='hidden' name='db_id' value="<?php echo $result['id']?>" />
<table cellpadding='0' cellspacing='0' border='0'>
	<tr>
		<td>
			<?php if ($GLOBALS['concurrent_layout']) { ?>
			<a href="demographics.php" onclick="top.restoreSession()">
			<?php } else { ?>
			<a href="patient_summary.php" target="Main" onclick="top.restoreSession()">
			<?php } ?>
			<font class=title><?php xl('Current Patient','e'); ?></font>
			</a>
			&nbsp;&nbsp;
		</td>
		<td>
			<a href="javascript:submitme();" class='css_button'>
				<span><?php xl('Save','e'); ?></span>
			</a>
		</td>
		<td>
			<?php if ($GLOBALS['concurrent_layout']) { ?>
			<a class="css_button" href="demographics.php" onclick="top.restoreSession()">
			<?php } else { ?>
			<a href="patient_summary.php" target="Main" onclick="top.restoreSession()">
			<?php } ?>
			<span><?php xl('Cancel','e'); ?></span>
			</a>
		</td>
	</tr>
</table>
<?php

function end_cell() {
  global $item_count, $cell_count;
  if ($item_count > 0) {
    echo "</td>";
    $item_count = 0;
  }
}

function end_row() {
  global $cell_count, $CPR;
  end_cell();
  if ($cell_count > 0) {
    for (; $cell_count < $CPR; ++$cell_count) echo "<td></td>";
    echo "</tr>\n";
    $cell_count = 0;
  }
}

function end_group() {
  global $last_group;
  if (strlen($last_group) > 0) {
    end_row();
    echo " </table>\n";
    echo "</div>\n";
  }
}

$last_group = '';
$cell_count = 0;
$item_count = 0;
$display_style = 'block';

$group_seq=0; // this gives the DIV blocks unique IDs

?>
<br>
  <div class="section-header">
   <span class="text"><b> <?php xl("Demographics", "e" )?></b></span>
</div>

<div id="DEM" >

	<!-- 
	<ul class="tabNav">
	   <?php //display_layout_tabs('DEM', $result, $result2); ?>
	     
	</ul>
	 -->
	 <div class="tabbable tabs-left">
              <ul class="nav nav-tabs">
                <li class="active"><a href="#lA" data-toggle="tab">Who</a></li>
                <li><a href="#lB" data-toggle="tab">Insurance</a></li>
                <li><a href="#lC" data-toggle="tab">Secondary Contact</a></li>
              </ul>
              <div class="tab-content">
                <div class="tab-pane active" id="lA">
                <ul data-bind="foreach: Persons">
                  <li>First Name: <input data-bind="value: firstName, click: $parent.selectPerson" /> </li>
                  <li>Last Name: <input data-bind="value: lastName" /></li>
                </ul>
                </div>
                <div class="tab-pane" id="lB">
                  <p>Insurance Stuff</p>
                </div>
                <div class="tab-pane" id="lC">
                  <p>Secondary Contact Info</p>
                  <div data-bind="with: selectedPerson">
                  <ul data-bind="foreach: SecondaryContacts"> 
                    <li>Relationship: <input data-bind="value: relationship" /> </li>
                    
                  </ul>
                  </div>
                </div>
              </div>
            </div> <!-- /tabbable -->

	<div class="tabContainer" >
		<!--  This was removed for the project. -->
		<?php //display_layout_tabs_data_editable('DEM', $result, $result2); ?>
		<?php //display_layout_tabs_data_ext('DEM', "edit", $result, $result2); ?>
		
</div>
	</div>
</div>
<br>
  <div class="section-header">
    <span class="text"><b><?php xl("Insurance", "e" )?></b></span>
</div>
<div id="DEM" >
<?php //fetch all infromation. ?>


<?php
 if (! $GLOBALS['simplified_demographics']) {

	  $insurance_headings = array(xl("Primary Insurance Provider"), xl("Secondary Insurance Provider"), xl("Tertiary Insurance provider"));
	  $insurance_info = array();
	  $insurance_info[1] = getInsuranceData($pid,"primary");
	  $insurance_info[2] = getInsuranceData($pid,"secondary");
	  $insurance_info[3] = getInsuranceData($pid,"tertiary");

	?>
	<div id="INSURANCE" >
		<ul class="tabNav">
		<?php
		foreach (array('primary','secondary','tertiary', '4-ary') as $instype) {
			?><li <?php echo $instype == 'primary' ? 'class="current"' : '' ?>><a href="/play/javascript-tabbed-navigation/"><?php $CapInstype=ucfirst($instype); xl($CapInstype,'e'); ?></a></li><?php
		}
		?>
		</ul>

	<div class="tabContainer">

	<?php
	  for($i=1;$i<=3;$i++) {
	   $result3 = $insurance_info[$i];
	?>

		<div class="tab <?php echo $i == 1 ? 'current': '' ?>" style='height:auto;width:auto'>		<!---display icky, fix to auto-->
		<!--  New Table -->
		<table border="0">
			<tr>
				<td class="required">Primary Insurance Provider</td>
				<td>: <a href="../../practice/ins_search.php" class="iframe medium_modal css_button" onclick="ins_search(<?php echo $i?>)">
						<span><?php echo xl('Search/Add') ?></span>
        			 </a>
        		</td>
				<td class="required">Plan Name</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="required">Policy Number</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">Effective Date</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="required">Group Number</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">Accept Assignment</td>
				<td>:   <select>
							<option value="Yes" selected>Yes</option>
							<option value="No">No</option>
						</select>
				</td>
			</tr>
			<!-- End insurance provider, start subscriber --> 
			<tr>
				<td colspan="4" style="background-color: red"></td>
			</tr>
			
			<tr>
				<td class="required">Subscriber</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">Relationship</td>
				<td>
					<a href="../../practice/person_search.php" class="iframe medium_modal css_button" onclick="person_search(<?php echo $i?>)">
						<span><?php echo xl('Search/Add') ?></span>
        			</a>
				</td>
			</tr>
			<tr>
				<td class="required">D.O.B.</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">Sex</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="required">S.S.</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td colspan="4" style="background-color: red"></td>
			</tr>
			<tr>
				<td class="required">Address</td>
				<td>
					<a href="../../practice/address_search.php" class="iframe medium_modal css_button" onclick="address_search(<?php echo $i?>)">
						<span><?php echo xl('Search/Add') ?></span>
        			</a>
				</td>
				
				<td colspan="2"></td>
				
			</tr>
			<tr>
				<td class="required">Subscriber Address</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">State</td>
				<td>State Select list</td>
			</tr>
			<tr>
				<td class="required">City</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">Country</td>
				<td>Country Select list</td>
			</tr>
			<tr>
				<td class="required">Zip Code</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td class="required">Subscriber Phone</td>
				<td>
					<a href="../../practice/person_phone_search.php" class="iframe medium_modal css_button" onclick="person_search(<?php echo $i?>)">
						<span><?php echo xl('Search/Add') ?></span>
        			</a>
				</td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td colspan="4" style="background-color: red"></td>
			</tr>
			<tr>
				<td colspan="2">Subscriber (SE) (if unemployed enter Student, PT student, or leave blank):</td>
				<td>
					<a href="../../practice/employer_search.php" class="iframe medium_modal css_button" onclick="employer_search(<?php echo $i?>)">
						<span><?php echo xl('Search/Add') ?></span>
        			</a>
				</td>
				<td></td>
			</tr>
			<tr>
				<td class="required">SE Address</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td class="required">SE City</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">SE State</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="required">SE Zip Code</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
				<td class="required">SE Country</td>
				<td>: <input type='entry' size='20' name='na'/>&nbsp;&nbsp;</td>
			</tr>
		</table>
		
		
		
		<table border="0">

		 <tr>
		  <td valign=top width="430">
		   <table border="0">

			 <tr>
			  <td valign='top'>
			   <span class='required'><?php echo $insurance_headings[$i -1]."&nbsp;"?></span>
			  </td>
			  <td class='required'>:</td>
			  <td>
                           <a href="../../practice/ins_search.php" class="iframe medium_modal css_button" onclick="ins_search(<?php echo $i?>)">
				<span><?php echo xl('Search/Add') ?></span>
        			</a>
				<select name="i<?php echo $i?>provider">
				<option value=""><?php xl('Unassigned','e'); ?></option>
				<?php
				 foreach ($insurancei as $iid => $iname) {
				  echo "<option value='" . $iid . "'";
				  if (strtolower($iid) == strtolower($result3{"provider"}))
				   echo " selected";
				  echo ">" . $iname . "</option>\n";
				 }
				?>
			   </select>

			  </td>
			 </tr>
			  <tr>
			  <td valign='top'>
			   <span class='required'>Relationship</span>
			  </td>
			  <td class='required'>:</td>
			  <td>
                           <a href="../../practice/person_search.php" class="iframe medium_modal css_button" onclick="ins_search(<?php echo $i?>)">
				<span><?php echo xl('Search/Add') ?></span>
        			</a>
				<select name="i<?php echo $i?>provider">
				<option value=""><?php xl('Unassigned','e'); ?></option>
				<?php
				 foreach ($insurancei as $iid => $iname) {
				  echo "<option value='" . $iid . "'";
				  if (strtolower($iid) == strtolower($result3{"provider"}))
				   echo " selected";
				  echo ">" . $iname . "</option>\n";
				 }
				?>
			   </select>

			  </td>
			 </tr>
			<tr>
			 <td>
			  <span class='required'><?php xl('Plan Name','e'); ?> </span>
			 </td>
			 <td class='required'>:</td>
			 <td>
			  <input type='entry' size='20' name='i<?php echo $i?>plan_name' value="<?php echo $result3{"plan_name"} ?>"
			   onchange="capitalizeMe(this);" />&nbsp;&nbsp;
			 </td>
			</tr>

			<tr>
			 <td>
			  <span class='required'><?php xl('Effective Date','e'); ?></span>
			 </td>
			 <td class='required'>:</td>
			 <td>
			  <input type='entry' size='16' id='i<?php echo $i ?>effective_date' name='i<?php echo $i ?>effective_date'
			   value='<?php echo $result3['date'] ?>'
			   onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
			   title='yyyy-mm-dd' />
                          <img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22' id='img_i<?php echo $i ?>effective_date' border='0' alt='[?]' style='cursor:pointer' title='<?php xl('Click here to choose a date','e'); ?>'>
			 </td>
			</tr>

			<tr>
			 <td><span class=required><?php xl('Policy Number','e'); ?></span></td>
			 <td class='required'>:</td>
			 <td><input type='entry' size='16' name='i<?php echo $i?>policy_number' value="<?php echo $result3{"policy_number"}?>"
			  onkeyup='policykeyup(this)'></td>
			</tr>

			<tr>
			 <td><span class=required><?php xl('Group Number','e'); ?></span></td>
			 <td class='required'>:</td>
			 <td><input type=entry size=16 name=i<?php echo $i?>group_number value="<?php echo $result3{"group_number"}?>" onkeyup='policykeyup(this)'></td>
			</tr>

			<tr<?php if ($GLOBALS['omit_employers']) echo " style='display:none'"; ?>>
			 <td class='required'><?php xl('Subscriber Employer (SE)','e'); ?><br><span style='font-weight:normal'>
			  (<?php xl('if unemployed enter Student','e'); ?>,<br><?php xl('PT Student, or leave blank','e'); ?>) </span></td>
			  <td class='required'>:</td>
			 <td><input type=entry size=25 name=i<?php echo $i?>subscriber_employer
			  value="<?php echo $result3{"subscriber_employer"}?>"
			   onchange="capitalizeMe(this);" /></td>
			</tr>

			<tr<?php if ($GLOBALS['omit_employers']) echo " style='display:none'"; ?>>
			 <td><span class=required><?php xl('SE Address','e'); ?></span></td>
			 <td class='required'>:</td>
			 <td><input type=entry size=25 name=i<?php echo $i?>subscriber_employer_street
			  value="<?php echo $result3{"subscriber_employer_street"}?>"
			   onchange="capitalizeMe(this);" /></td>
			</tr>

			<tr<?php if ($GLOBALS['omit_employers']) echo " style='display:none'"; ?>>
			 <td colspan="3">
			  <table>
			   <tr>
				<td><span class=required><?php xl('SE City','e'); ?>: </span></td>
				<td><input type=entry size=15 name=i<?php echo $i?>subscriber_employer_city
				 value="<?php echo $result3{"subscriber_employer_city"}?>"
				  onchange="capitalizeMe(this);" /></td>
				<td><span class=required><?php echo ($GLOBALS['phone_country_code'] == '1') ? xl('SE State','e') : xl('SE Locality','e') ?>: </span></td>
			<td>
				 <?php
				  // Modified 7/2009 by BM to incorporate data types
			  generate_form_field(array('data_type'=>$GLOBALS['state_data_type'],'field_id'=>('i'.$i.'subscriber_employer_state'),'list_id'=>$GLOBALS['state_list'],'fld_length'=>'15','max_length'=>'63','edit_options'=>'C'), $result3['subscriber_employer_state']);
				 ?>
				</td>
			   </tr>
			   <tr>
				<td><span class=required><?php echo ($GLOBALS['phone_country_code'] == '1') ? xl('SE Zip Code','e') : xl('SE Postal Code','e') ?>: </span></td>
				<td><input type=entry size=15 name=i<?php echo $i?>subscriber_employer_postal_code value="<?php echo $result3{"subscriber_employer_postal_code"}?>"></td>
				<td><span class=required><?php xl('SE Country','e'); ?>: </span></td>
			<td>
				 <?php
				  // Modified 7/2009 by BM to incorporate data types
			  generate_form_field(array('data_type'=>$GLOBALS['country_data_type'],'field_id'=>('i'.$i.'subscriber_employer_country'),'list_id'=>$GLOBALS['country_list'],'fld_length'=>'10','max_length'=>'63','edit_options'=>'C'), $result3['subscriber_employer_country']);
				 ?>
			</td>
			   </tr>
			  </table>
			 </td>
			</tr>

		   </table>
		  </td>

		  <td valign=top>
		<table border="0">
			<tr>
				<td><span class=required><?php xl('Relationship','e'); ?></span></td>
				<td class=required>:</td>
				<td colspan=3><?php
					// Modified 6/2009 by BM to use list_options and function
					generate_form_field(array('data_type'=>1,'field_id'=>('i'.$i.'subscriber_relationship'),'list_id'=>'sub_relation','empty_title'=>' '), $result3['subscriber_relationship']);
					?>

				<a href="javascript:popUp('browse.php?browsenum=<?php echo $i?>')" class=text>(<?php xl('Browse','e'); ?>)</a></td>
				<td></td><td></td><td></td><td></td>
			</tr>
                        <tr>
				<td width=120><span class=required><?php xl('Subscriber','e'); ?> </span></td>
				<td class=required>:</td>
				<td colspan=3><input type=entry size=10 name=i<?php echo $i?>subscriber_fname	value="<?php echo $result3{"subscriber_fname"}?>" onchange="capitalizeMe(this);" />
				<input type=entry size=3 name=i<?php echo $i?>subscriber_mname value="<?php echo $result3{"subscriber_mname"}?>" onchange="capitalizeMe(this);" />
				<input type=entry size=10 name=i<?php echo $i?>subscriber_lname value="<?php echo $result3{"subscriber_lname"}?>" onchange="capitalizeMe(this);" /></td>
				<td></td><td></td><td></td><td></td>
			</tr>
			<tr>
				<td><span class=bold><?php xl('D.O.B.','e'); ?> </span></td>
				<td class=required>:</td>
				<td><input type='entry' size='11' id='i<?php echo $i?>subscriber_DOB' name='i<?php echo $i?>subscriber_DOB' value='<?php echo $result3['subscriber_DOB'] ?>' onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' title='yyyy-mm-dd' /><img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22' id='img_i<?php echo $i; ?>dob_date' border='0' alt='[?]' style='cursor:pointer' title='<?php xl('Click here to choose a date','e'); ?>'></td>

				<td><span class=bold><?php xl('Sex','e'); ?>: </span></td>
				<td><?php
					// Modified 6/2009 by BM to use list_options and function
					generate_form_field(array('data_type'=>1,'field_id'=>('i'.$i.'subscriber_sex'),'list_id'=>'sex'), $result3['subscriber_sex']);
					?>
				</td>
				<td></td><td></td> <td></td><td></td>
			</tr>
			<tr>
				<td class=leftborder><span class=bold><?php xl('S.S.','e'); ?> </span></td>
				<td class=required>:</td>
				<td><input type=entry size=11 name=i<?php echo $i?>subscriber_ss value="<?php echo $result3{"subscriber_ss"}?>"></td>
			</tr>

			<tr>
				<td><span class=required><?php xl('Subscriber Address','e'); ?> </span></td>
				<td class=required>:</td>
				<td><input type=entry size=20 name=i<?php echo $i?>subscriber_street value="<?php echo $result3{"subscriber_street"}?>" onchange="capitalizeMe(this);" /></td>

				<td><span class=required><?php echo ($GLOBALS['phone_country_code'] == '1') ? xl('State','e') : xl('Locality','e') ?>: </span></td>
				<td>
					<?php
					// Modified 7/2009 by BM to incorporate data types
					generate_form_field(array('data_type'=>$GLOBALS['state_data_type'],'field_id'=>('i'.$i.'subscriber_state'),'list_id'=>$GLOBALS['state_list'],'fld_length'=>'15','max_length'=>'63','edit_options'=>'C'), $result3['subscriber_state']);
				?>
				</td>
			</tr>
			<tr>
				<td class=leftborder><span class=required><?php xl('City','e'); ?></span></td>
				<td class=required>:</td>
				<td><input type=entry size=11 name=i<?php echo $i?>subscriber_city value="<?php echo $result3{"subscriber_city"}?>" onchange="capitalizeMe(this);" /></td><td class=leftborder><span class='required'<?php if ($GLOBALS['omit_employers']) echo " style='display:none'"; ?>><?php xl('Country','e'); ?>: </span></td><td>
					<?php
					// Modified 7/2009 by BM to incorporate data types
					generate_form_field(array('data_type'=>$GLOBALS['country_data_type'],'field_id'=>('i'.$i.'subscriber_country'),'list_id'=>$GLOBALS['country_list'],'fld_length'=>'10','max_length'=>'63','edit_options'=>'C'), $result3['subscriber_country']);
					?>
				</td>
</tr>
			<tr>
				<td><span class=required><?php echo ($GLOBALS['phone_country_code'] == '1') ? xl('Zip Code','e') : xl('Postal Code','e') ?> </span></td><td class=required>:</td><td><input type=entry size=10 name=i<?php echo $i?>subscriber_postal_code value="<?php echo $result3{"subscriber_postal_code"}?>"></td>

				<td colspan=2>
				</td><td></td>
			</tr>
			<tr>
				<td><span class=bold><?php xl('Subscriber Phone','e'); ?></span></td>
				<td class=required>:</td>
				<td><input type='text' size='20' name='i<?php echo $i?>subscriber_phone' value='<?php echo $result3["subscriber_phone"] ?>' onkeyup='phonekeyup(this,mypcc)' /></td>
				<td colspan=2><span class=bold><?php xl('CoPay','e'); ?>: <input type=text size="6" name=i<?php echo $i?>copay value="<?php echo $result3{"copay"}?>"></span></td>
				<td colspan=2>
				</td><td></td><td></td>
			</tr>
			<tr>
				<td colspan=0><span class='required'><?php xl('Accept Assignment','e'); ?></span></td>
				<td class=required>:</td>
				<td colspan=2>
					<select name=i<?php echo $i?>accept_assignment>
						<option value="TRUE" <?php if (strtoupper($result3{"accept_assignment"}) == "TRUE") echo "selected"?>><?php xl('YES','e'); ?></option>
						<option value="FALSE" <?php if (strtoupper($result3{"accept_assignment"}) == "FALSE") echo "selected"?>><?php xl('NO','e'); ?></option>
					</select>
				</td>
				<td></td><td></td>
				<td colspan=2>
				</td><td></td>
			</tr>
      <tr>
        <td><span class='bold'><?php xl('Secondary Medicare Type','e'); ?></span></td>
        <td class='bold'>:</td>
        <td colspan='6'>
          <select name=i<?php echo $i?>policy_type>
<?php
  foreach ($policy_types AS $key => $value) {
    echo "            <option value ='$key'";
    if ($key == $result3['policy_type']) echo " selected";
    echo ">" . htmlspecialchars($value) . "</option>\n";
  }
?>
          </select>
        </td>
      </tr>
		</table>

		  </td>
		 </tr>
		</table>

		</div>

	<?php } //end insurer for loop ?>

	</div>
</div>

<?php } // end of "if not simplified_demographics" ?>
</div></div>

</form>

<br>

<style>
body
{
    margin: 0 auto;
    padding: 0 20px;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #555;
}
table
{
    border: 0;
    padding: 0;
    margin: 0 0 20px 0;
    border-collapse: collapse;
    
}
th
{
    padding: 5px; /* NOTE: th padding must be set explicitly in order to support IE */
    text-align: right;        
    font-weight:bold;
    line-height: 2em;
    color: #FFF;
    background-color: #555;
}
tbody td
{
    padding: 10px;
    line-height: 18px;
    border-top: 1px solid #E0E0E0;
}
tbody tr:nth-child(2n)
{
    background-color: #F7F7F7;
}
tbody tr:hover
{
    background-color: #EEEEEE;
}
td
{
    text-align: right;
}
td:first-child, th:first-child
{
    text-align: left;
}
</style>

<script language="javascript">
	
	$("#addRow").click(function() {
		$(this).parent().find("table tr:last").clone().find("input").each(function() {
			$(this).attr({
				'id': function() { 
					console.debug("ID is "+$(this).attr("id"));
					
					var regEx = /([^\]]+)(\d)(.*)/;
					var match = regEx.exec($(this).attr("id"));
					console.debug("1=>%s,2=>%s,3=>%s",match[1],match[2],match[3]);
					return match[1]+(parseInt(match[2])+1)+match[3]; 
					},
				'name': function() { 
					
					var regEx = /([^\]]+)(\d)(.*)/;
					var match = regEx.exec($(this).attr("name"));
					console.debug("1=>%s,2=>%s,3=>%s",match[1],match[2],match[3]);
					return match[1]+(parseInt(match[2])+1)+match[3];  },
				'value':  function() {
					var regEx = /([^\]]+)(\d)(.*)/;
					var match = regEx.exec($(this).attr("name"));
					console.debug("1=>%s,2=>%s,3=>%s",match[1],match[2],match[3]);
					if(match[3] == "][db_action]") {
						return "insert";
					}
					else {
						return "";
					}
				  }
			});
		}).end().appendTo($(this).parent().find("table"));
		
	});


$(function(){
    $(".tableRepeat").stickyTableHeaders();
});

/*! Copyright (c) 2011 by Jonas Mosbech - https://github.com/jmosbech/StickyTableHeaders
    MIT license info: https://github.com/jmosbech/StickyTableHeaders/blob/master/license.txt */

;(function ($, window, undefined) {
    'use strict';

    var pluginName = 'stickyTableHeaders';
    var defaults = {
            fixedOffset: 0
        };

    function Plugin (el, options) {
        // To avoid scope issues, use 'base' instead of 'this'
        // to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Cache DOM refs for performance reasons
        base.$window = $(window);
        base.$clonedHeader = null;
        base.$originalHeader = null;

        // Keep track of state
        base.isCloneVisible = false;
        base.leftOffset = null;
        base.topOffset = null;

        base.init = function () {
            base.options = $.extend({}, defaults, options);

            base.$el.each(function () {
                var $this = $(this);

                // remove padding on <table> to fix issue #7
                $this.css('padding', 0);

                $this.wrap('<div class="divTableWithFloatingHeader"></div>');

                base.$originalHeader = $('thead:first', this);
                base.$clonedHeader = base.$originalHeader.clone();

                base.$clonedHeader.addClass('tableFloatingHeader');
                base.$clonedHeader.css({
                    'position': 'fixed',
                    'top': 0,
                    'z-index': 1, // #18: opacity bug
                    'display': 'none'
                });

                base.$originalHeader.addClass('tableFloatingHeaderOriginal');

                base.$originalHeader.after(base.$clonedHeader);

                // enabling support for jquery.tablesorter plugin
                // forward clicks on clone to original
                $('th', base.$clonedHeader).click(function (e) {
                    var index = $('th', base.$clonedHeader).index(this);
                    $('th', base.$originalHeader).eq(index).click();
                });
                $this.bind('sortEnd', base.updateWidth);
            });

            base.updateWidth();
            base.toggleHeaders();

            base.$window.scroll(base.toggleHeaders);
            base.$window.resize(base.toggleHeaders);
            base.$window.resize(base.updateWidth);
        };

        base.toggleHeaders = function () {
            base.$el.each(function () {
                var $this = $(this);

                var newTopOffset = isNaN(base.options.fixedOffset) ?
                    base.options.fixedOffset.height() : base.options.fixedOffset;

                var offset = $this.offset();
                var scrollTop = base.$window.scrollTop() + newTopOffset;
                var scrollLeft = base.$window.scrollLeft();

                if ((scrollTop > offset.top) && (scrollTop < offset.top + $this.height())) {
                    var newLeft = offset.left - scrollLeft;
                    if (base.isCloneVisible && (newLeft === base.leftOffset) && (newTopOffset === base.topOffset)) {
                        return;
                    }

                    base.$clonedHeader.css({
                        'top': newTopOffset,
                        'margin-top': 0,
                        'left': newLeft,
                        'display': 'block'
                    });
                    base.$originalHeader.css('visibility', 'hidden');
                    base.isCloneVisible = true;
                    base.leftOffset = newLeft;
                    base.topOffset = newTopOffset;
                }
                else if (base.isCloneVisible) {
                    base.$clonedHeader.css('display', 'none');
                    base.$originalHeader.css('visibility', 'visible');
                    base.isCloneVisible = false;
                }
            });
        };

        base.updateWidth = function () {
            // Copy cell widths and classes from original header
            $('th', base.$clonedHeader).each(function (index) {
                var $this = $(this);
                var $origCell = $('th', base.$originalHeader).eq(index);
                this.className = $origCell.attr('class') || '';
                $this.css('width', $origCell.width());
            });

            // Copy row width from whole table
            base.$clonedHeader.css('width', base.$originalHeader.width());
        };

        // Run initializer
        base.init();
    }

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function ( options ) {
        return this.each(function () {
            if (!$.data(this, 'plugin_' + pluginName)) {
                $.data(this, 'plugin_' + pluginName, new Plugin( this, options ));
            }
        });
    };

})(jQuery, window);

</script>


<script language="JavaScript">

 // fix inconsistently formatted phone numbers from the database
 var f = document.forms[0];
 if (f.form_phone_contact) phonekeyup(f.form_phone_contact,mypcc);
 if (f.form_phone_home   ) phonekeyup(f.form_phone_home   ,mypcc);
 if (f.form_phone_biz    ) phonekeyup(f.form_phone_biz    ,mypcc);
 if (f.form_phone_cell   ) phonekeyup(f.form_phone_cell   ,mypcc);

<?php if (! $GLOBALS['simplified_demographics']) { ?>
 phonekeyup(f.i1subscriber_phone,mypcc);
 phonekeyup(f.i2subscriber_phone,mypcc);
 phonekeyup(f.i3subscriber_phone,mypcc);
<?php } ?>

<?php if ($GLOBALS['concurrent_layout'] && $set_pid) { ?>
 parent.left_nav.setPatient(<?php echo "'" . addslashes($result['fname']) . " " . addslashes($result['lname']) . "',$pid,'" . addslashes($result['pubpid']) . "','', ' " . xl('DOB') . ": " . oeFormatShortDate($result['DOB_YMD']) . " " . xl('Age') . ": " . getPatientAge($result['DOB_YMD']) . "'"; ?>);
 parent.left_nav.setRadio(window.name, 'dem');
<?php } ?>

<?php echo $date_init; ?>
<?php if (! $GLOBALS['simplified_demographics']) { for ($i=1; $i<=3; $i++): ?>
 Calendar.setup({inputField:"i<?php echo $i?>effective_date", ifFormat:"%Y-%m-%d", button:"img_i<?php echo $i?>effective_date"});
 Calendar.setup({inputField:"i<?php echo $i?>subscriber_DOB", ifFormat:"%Y-%m-%d", button:"img_i<?php echo $i?>dob_date"});
<?php endfor; } ?>
</script>

<!-- include support for the list-add selectbox feature -->
<?php include $GLOBALS['fileroot']."/library/options_listadd.inc"; ?>

<script type="application/javascript;version=1.7">
var personObject = {
	persons:[
	       	{
			  person_id: 44,
			  firstName: "Chris",
			  lastName :"Paulus",
			  Addresses: [
				    { type_id : 13,
				      address_id:144,
				      priority:1,
				      street_line_1:"123 E St.",
				      street_line_2:"Apt 23",
				      city:"Tampa",
				      state:"Florida",
				      postal:"02025-2344"
				    },
				    {
				      type_id : 14,
				      address_id:145,
				      priority:2,
				      street_line_1:"44 Baker St.",
				      street_line_2:"",
				      city:"Plant City",
				      state:"Florida",
				      postal:"02025-2344"
				    },
			  ],
			  PhoneNums:[
				    {
				      phone_id:5,
				      address_id:13,
				      type_id : 14,
				      priority:2,
				      telephone_number:"81322244400"
				    }, 
				    {
				      phone_id:7,
				      address_id:13,
				      type_id : 14,
				      priority:1,
				      telephone_number:"7274445511"
				    }
			  ], 
			  SecondaryContacts : [
				    {
				      person_id: 45,
				      priority:1,
				      relationship:"wife"
				    },
				    {
				      person_id: 47,
				      priority:2,
				      relationship:"cousin"
				    }
			  ]
	}]};
$(document).ready(_.once(function() {
	var viewModelObject = {
		viewModel : null,
		orginalData : null,
		initialize : _.once(function(dataJs) {
			var data = dataJs,
				self = this,
				self2 = this,  
			    personId = 1, //Need to update via jquery
			    subscription = 1, //Implement later
			    selectedPerson = ko.observable();
			    selectPerson = function(person) {
				    var clonePerson = ko.mapping.fromJS(person);
				    data.selectedPerson(clonePerson);
			    },
			    getPersons = function() {
				    var lclArray = [];
				    for(var i = 0; i<personObject.persons.length;i++) { 
					    //personObject.persons.forEach(function(element, index, arrayList) {
						//	lclArray.push({  person_id : ko.observable(element.person_id), 
						//		             firstName : ko.observable(element.firstName), 
						//		             lastName  : ko.observable(element.lastName) });
				        //     
					    //});
					    var lclPerson = personObject.persons[i];
					    var lclObject = 
					    {  
						    person_id : ko.observable(lclPerson.person_id), 
							firstName : ko.observable(lclPerson.firstName), 
							lastName  : ko.observable(lclPerson.lastName),
							SecondaryContacts : ko.observableArray([ ]) 
						};
					    for(var j =0; j < lclPerson.SecondaryContacts.length; j++) {
							var lclPersonContact = lclPerson.SecondaryContacts[j];
							var lclContact = 
					    	
							    	{relationship: ko.observable(lclPersonContact.relationship)};
							
							personObject.persons[i].SecondaryContacts[j].relationship= ko.observable(lclPersonContact.relationship);
					    	var index = j;
							//lclContact.relationship.subscribe(function(newValue) {
								//lclPersonContact.relationship(newValue);
								
							//	console.log("person contact id: "+self.viewModel.Persons);
						    //});
						    lclContact.relationship.subscribe(new Function('newValue',
								    //'console.log("person contact id: "+'+index+');'));
						    		'personObject.persons['+i+'].SecondaryContacts['+j+'].relationship(newValue);'));

						    //old code
						    	//self.viewModel.Persons()[0]
								//lclPersonContact.relationship(newValue);
									
						    //personObject.persons[i].SecondaryContacts[j].relationship.subscribe(
								//    new Function('newValue',
								//'self.viewModel.Persons()['+i+'].SecondaryContacts['+j+'].relationship(newValue);'
								//'console.log("self2");\n'+
								//'new Function(\'\',\'console.log("self2"+JSON.stringify(self.viewModel.Persons()[0].firstName()));\')();'
								//));

						    //This works.
							personObject.persons[i].SecondaryContacts[j].relationship.subscribe(function(newValue) {
										
									//console.log("person contact id: "+self.viewModel.Persons()[0].firstName());
								console.log("person contact id: "+this.relationship(newValue));
								}, lclContact);
						    
						    lclObject.SecondaryContacts.push(lclContact);
					    }
					    lclPerson.firstName = ko.observable(lclPerson.firstName);
					    lclObject.firstName.subscribe(function(newValue) {
						    lclPerson.firstName(newValue);
					    });
					    lclPerson.firstName.subscribe(function(newValue) {
					    	lclObject.firstName(newValue);
					    });
					    data.Persons.push(lclObject);
						
				    }
				    
				    
			    }; //last one gets a ";"
			    data.getPersons = function() {
				    getPersons();
			    };
			    data.selectPerson = function(person) {
				    selectPerson(person);
			    };
			    
				data['self']=this;
			    ko.applyBindings(data, window.document.body);
	
			    this.viewModel = data;

			    
			    
			    data.getPersons();
			     

							    
			    //data.Persons()[0].firstName.subscribe(function(newValue) {
				//    console.log("the New value is "+newValue);
				//    personObject.persons[0].firstName( newValue);
			    //});
			    //personObject.persons[0].firstName = ko.observable(personObject.persons[0].firstName);

			    //personObject.persons[0].firstName.subscribe(function(newValue) {
			    //	data.Persons()[0].firstName(newValue);
			    	//console.log("Output is: "+ this);
			    //}, lcl);
			    
		}) 
	};
	
		viewModelObject.initialize({
			Persons: ko.observableArray([]),
			selectedPerson : ko.observable()
		});	
		

}));
	
</script>

</body>

</html>
