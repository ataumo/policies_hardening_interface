<?php

//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!//
// Do not forget to define $link_csv_file before include this file.
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!//

//
// This code will load all categories in $ALL_categories_content to write them
// in the left nav bar and load all parsed policies in $ALL_table_content
//

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////  Variable part ////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


/* Global variable is used for keep the last category */
$global_categ = "init";
$done_policies_nbr = 0;
$checked_policies_nbr = 0;
$policies_nbr = 0;

$ALL_table_content = "";
$ALL_categories_content = "";
$ALL_progress_view = "";
$ALL_counter_view = "";


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////  Function part ////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


/* clean command */
// Input : String
// Output : String
// Exemple :
// [Disable-WindowsOptionalFeature -Online smb1protocol] to
//[<span class="code-command">Disable-WindowsOptionalFeature </span><span class="code-option">-Online </span><span class="code-argument">smb1protocol </span>]
function CleanCommand($command)
{
  $command_return="";
  $command = str_replace("  ", " ", $command); // clean command
  $commands_array = explode(";", $command);
  // for each command
  foreach ($commands_array as $command_key => $command_content) {
    $command_array = explode(" ", $command_content);
    if ($command_array[0]=="") {
      array_shift($command_array); // remove first first space
    }
    $command_return = $command_return."<span class=\"code-command\">$command_array[0] </span>";
    array_shift($command_array); // remove first element
    if (count($command_array)>=1) {
      // for each arg and values
      foreach ($command_array as $key => $value) {
        if (strlen($value)>=1) {
          // if it is an argument (with '-')
          if ($value[0]=='-' || $value[0]=='/' || $value[0]=='+') {
            $command_return = $command_return."<span class=\"code-option\">$value </span>";
          }else {
            $command_return = $command_return."<span class=\"code-argument\">$value </span>";
          }
        }

      }
    }
    $command_return = $command_return." ; ";
  }
  return $command_return;
}


/* clean text */
// Input : String
// Output : String
// Example :
// [->item1 \n ->item2]  to  [<ul><li>item1</li><li>item2</li></ul>]
function CleanText($string){
  $string_content = $string;
  $first_occur_pos = strpos($string, '->');
  if ($first_occur_pos !== false) {
    //replace first occurence
    $string_content = substr_replace($string_content, "<ul><li>", $first_occur_pos, strlen('->'));
    $string_content = str_replace("->", "</li><li>", $string_content);
    $string_content = $string_content."</li></ul>";
  }else {
    $string_content = str_replace("\n", "<br>", $string_content);
  }
  return $string_content;
}

/* FileParser function is used to print row's policies */
// Input : Array
function FileParser($data)
{
  global $global_categ;
  global $ALL_table_content;
  global $ALL_categories_content;
  // define all values to a void string
  $id = $category = $name= $method=
  $method_argument= $registry_path= $registry_item= $classname= $namespace=
  $property= $default_value= $recommended_value= $operator= $severity=
  $UIX_impact= $use= $use_mode= $intro= $link_for_more_infos= $tags=
  $consequences= $advice= $notes= $setting_command= $getting_command=
  $comment= $possible_values= $operating_system = "";

  //// Getting values ////
  // If we have all columns in CSV
  if (count($data)>14) {
    list($id, $category, $name, $method,
    $method_argument, $registry_path, $registry_item, $classname, $namespace,
    $property, $default_value, $recommended_value, $operator, $severity,
    $UIX_impact, $use, $use_mode, $intro, $link_for_more_infos, $tags,
    $consequences, $advice, $notes, $setting_command, $getting_command,
    $comment, $possible_values, $operating_system) = $data;
  }else {
    list($id, $category, $name, $method,
    $method_argument, $registry_path, $registry_item, $classname, $namespace,
    $property, $default_value, $recommended_value, $operator, $severity) = $data;
  }


  /* clean id string */
  // to replace '.' with '-'
  $id_cleaned = str_replace(".", "-", $id);


  /* category title printing */
  if ($global_categ!=$category) {
    $categoty_content = preg_replace("/[^a-zA-Z0-9]+/", "", $category);
    $ALL_table_content = $ALL_table_content."
    <tr id=\"$categoty_content-$id_cleaned\">
      <td colspan=\"8\" class=\"table-secondary title-tr\">
        <h4>$category</h4>
      </td>
    </tr>
    ";
    $ALL_categories_content = $ALL_categories_content."
    <a class=\"list-group-item list-group-item-action\" href=\"#$categoty_content-$id_cleaned\"><b>$id</b><span class=\"d-none d-xxl-inline\"> - $category</span></a>
    ";
    $global_categ = $category;
  }


  /**** coloring part ****/
  // Color code :
  // table-danger : policy whose introduction is not written (considered not done)
  // table-warning : policy whose UIX_impact category is defined with '?' (considered not completed)
  // table-orange : policy whose use category is defined with '?' (considered not completed)
  global $done_policies_nbr;
  global $checked_policies_nbr;
  $class_content = "";
  if ($intro=="") {
    $class_content = "table-danger";
    $done_policies_nbr = $done_policies_nbr+1;
  }else {
    if ($UIX_impact=='?') {
      $class_content = "table-warning";
    }
    if ($use=='?') {
      $class_content = "table-orange";
    }
  }
  if ($use=="1") {
    $checked_policies_nbr = $checked_policies_nbr+1;
  }
  /********/

  $class_content_global = "content-$id_cleaned";


  /**** Compute some values before row printing ****/

  /* severity */
  $relevance_severity=0;
  switch ($severity) {
    case 'High':
      $severity="<span class=\"badge bg-danger\">High</span>";
      $relevance_severity=3;
      break;
    case 'Medium':
      $severity="<span class=\"badge bg-secondary\">Medium</span>";
      $relevance_severity=2;
      break;
    case 'Low':
      $severity="<span class=\"badge bg-primary\">Low</span>";
      $relevance_severity=1;
      break;
  }

  /* UIX impact */
  $UIX_impact_content = "<span class=\"badge bg-secondary\">Not defined</span>";
  $relevance_uix=0;
  switch ($UIX_impact) {
    case '2':
      $UIX_impact_content="<span class=\"badge bg-danger\">Impact</span>";
      $relevance_uix=1;
      break;
    case '1':
      $UIX_impact_content="<span class=\"badge bg-warning text-dark\">Potentially</span>";
      $relevance_uix=2;
      break;
    case '0':
      $UIX_impact_content="<span class=\"badge bg-success\">No impact</span>";
      $relevance_uix=3;
      break;
  }

  /* default value */
  $default_value_content = str_replace(";", ";<br>", $default_value);
  $default_value_content = str_replace(",", ",<br>", $default_value_content);
  $default_value_content = "<code>$default_value_content</code>";

  /* recommended value */
  $recommended_value_content = str_replace(";", ";<br>", $recommended_value);
  $recommended_value_content = str_replace(",", ",<br>", $recommended_value_content);
  $recommended_value_content = "<code>$recommended_value_content</code>";

  /* use check box */
  $check_box_value = "not-checked";
  if ($use=="1") {
    $check_box_value="checked";
  }

  /* Mode */
  $use_mode_content="";
  $relevance_mode=0;
  if ($use_mode) {
    switch ($use_mode) {
      case 'Basic':
        $use_mode_content = "<span class=\"badge bg-light text-dark m-1\" style=\"border:solid 1px;\">$use_mode</span>";
        $relevance_mode = 3;
        break;
      case 'Enterprise':
        $use_mode_content = "<span class=\"badge bg-secondary m-1\">$use_mode</span>";
        $relevance_mode = 2;
        break;
      case 'StrongBox':
        $use_mode_content = "<span class=\"badge bg-dark m-1\">$use_mode</span>";
        $relevance_mode = 1;
        break;

      default:
        // code...
        break;
    }
  }

  /**** Row printing ****/
  // in this part, in csv-data, we use ,-, to separate values
  $ALL_table_content = $ALL_table_content."
  <tr id=\"data-$id\" class=\"$class_content $class_content_global tr-visible row-content\" csv-data=\"$id,-,$recommended_value,-,$check_box_value\" data-tags=\"$tags\" active-filter-uix=\"false\" active-filter-severity=\"false\">
    <th class=\"btn-link csv-id\" scope=\"row\" data-bs-toggle=\"collapse\" data-bs-target=\"#data-content-$id_cleaned\" role=\"button\" data-target=\"#data-content-$id_cleaned\">
      <a href=\"#data-$id\" >$id</a>
    </th>
    <td>
      <input class=\"form-check-input check-policy\" type=\"checkbox\" value=\"\" $check_box_value>
    </td>
    <td class=\"mode\"> $use_mode_content</td>
    <td class=\"policy-name\">$name</td>
    <td class=\"severity\" data-content=\"data-content-$id_cleaned\" >$severity</td>
    <td class=\"uix_impact\" >$UIX_impact_content</td>
    <td>$default_value_content</td>
    <td class=\"value_to_use\">$recommended_value_content</td>
    <!--<td class=\"value_to_use\">
      <select id=\"selected-uix\" class=\"form-select form-select-sm\" aria-label=\"Default select example\">
              <option value='0' selected=''><code>$recommended_value_content</code></option>
              <option value='1'>Impact</option>
              <option value='2'>Potentially</option>
              <option value='3'>No impact</option>
      </select>
    </td>-->
  </tr>";


  //**** Compute some values before content printing ****//
  /* intro */
  $intro_content = CleanText($intro);

  /* Consequences */
  $consequences_content = "";
  if ($consequences!="") {
    $consequences_content = CleanText($consequences);
    $consequences_content="<h4>Potential impact</h4><div class=\"alert alert-danger\" role=\"alert\">$consequences_content</div>";
  }

  /* Advices */
  $advice_content = "";
  if ($advice!="") {
    $advice_content = CleanText($advice);
    $advice_content="<h4>Advices</h4><div class=\"alert alert-success\" role=\"alert\">$advice_content</div>";
  }

  /* Notes */
  $notes_content = "";
  if ($notes) {
    $notes_content = CleanText($notes);
    $notes_content="<h4>Notes</h4><div class=\"alert alert-secondary\" role=\"alert\">$notes_content</div>";
  }

  /* OS */
  $operating_system_content = "";
  $operating_system_string = str_replace(";", "</li><li>", $operating_system);
  if ($operating_system) {
    $operating_system_content = "<h4>OS</h4><div class=\"alert alert-primary\" role=\"alert\">
    <ul>
      <li>$operating_system_string</li>
    </ul>
    </div>";
  }

  /* Command */
  // Getting and setting commands are automatically generated for Registry method policies
  $command_content ="";
  if ($method == "Registry" || $getting_command || $setting_command) {
    $command_content = "<h4>Powershell Command</h4>";
    if ($method == "Registry") {
      $command_get = "<span class=\"code-command\">Get-ItemProperty </span><span class=\"code-option\">-path </span><span class=\"code-argument\">'$registry_path' </span><span class=\"code-option\">-name </span><span class=\"code-argument\">'$registry_item'</span>";
      $command_set = "<span class=\"code-command\">Set-ItemProperty </span><span class=\"code-option\">-path </span><span class=\"code-argument\">'$registry_path' </span><span class=\"code-option\">-name </span><span class=\"code-argument\">'$registry_item' </span><span class=\"code-option\">-value </span><span class=\"code-argument\">$recommended_value</span>";
      $command_content = $command_content."
      <h6>Get Value : </h6>
      <div class=\"div-command-pre-wrapper\">
      <code>$command_get</code>
      </div>
      <h6>Set Value : </h6>
      <div class=\"div-command-pre-wrapper\">
      <code> $command_set</code>
      </div>
      <br>";
    }else {
      if ($getting_command) {
        $command_get = CleanCommand($getting_command);
        $command_content = $command_content."
        <h6>Getting Command : </h6>
        <div class=\"div-command-pre-wrapper\">
        <code>$command_get</code>
        </div>";
      }
      if ($setting_command) {
        $command_set = CleanCommand($setting_command);
        $command_content = $command_content."
        <h6>Setting Command : </h6>
        <div class=\"div-command-pre-wrapper\">
        <code> $command_set</code>
        </div>";
      }
    }
    $command_content = $command_content."<br>";
  }

  /* Method */
  $method_content="";
  if ($method) {
    $method_argument_content = "";
    if ($method_argument) {
      $method_argument_content = "
      <h6>Method Argument :</h6>
      <div class=\"div-command-pre-wrapper\">
        <code>
          <span class=\"code-argument\">$method_argument</span>
        </code>
      </div>";
    }
    $method_content = "
    <h4>Method</h4>
    <h6>Method : </h6>
    <div class=\"div-command-pre-wrapper\">
      <code>
        <span class=\"code-command\">$method</span>
      </code>
    </div>
    $method_argument_content
    <br>";
  }

  /* Registry */
  $registry_content="";
  if ($registry_path) {
    //$registry_path_content = str_replace("\\", "<br>\\", $registry_path);
    $registry_content = "
    <h4>Registry</h4>
    <h6>RegistryPath : </h6>
    <div class=\"div-command-pre-wrapper\">
      <code><span class=\"code-argument\">$registry_path</span></code>
    </div>
    <h6>RegistryItem : </h6>
    <div class=\"div-command-pre-wrapper\">
      <code><span class=\"code-argument\">$registry_item</span></code>
    </div>
    <br>";
  }

  /* Tags */
  $tags_content="";
  if ($tags) {
    $tags_array = explode(";", $tags);
    foreach ($tags_array as $tag) {
      $tags_content = "$tags_content<span class=\"badge rounded-pill bg-dark m-1\">$tag</span>";
    }
  }

  /* relevance */
  $relevance_result = $relevance_severity*3+$relevance_uix*2+$relevance_mode*1;
  $relevance_result = $relevance_result/6;
  $relevance_percent = ($relevance_result-1)*100/2;
  $relevance_percent = round($relevance_percent);

  /* Values */
  $possible_values_array = explode(":", $possible_values);
  $type = $possible_values_array[0];
  $values="";
  if (count($possible_values_array)>1) {
    $values = $possible_values_array[1];
    $values = str_replace(";", "</li><li>", $values);
  }





  //**** Blank row printing ****//
  $ALL_table_content = $ALL_table_content."<tr class=\"$class_content_global\" ></tr>";

  //**** Content row printing ****//
  $ALL_table_content = $ALL_table_content."
  <!---- Toggle content ---->
  <tr class=\"$class_content_global\">
    <td colspan=\"8\" class=\"hiddenRow\">
      <div id=\"data-content-$id_cleaned\" class=\"accordian-body collapse\" style=\"\">
        <div class=\"p-xl-5 p-md-3 p-sm-2\">

          <!---- Header content ---->
          <div>
            <h2>$name</h2>
            <div>$tags_content</div>
          </div>

          <hr>

          <!---- Data content ---->
          <div class=\"row align-items-start\">

            <!-- Introduction -->
            <div class=\"col-4\">
              <h3>Introduction</h3>
              <hr>
                <p>$intro_content</p>
                <a target=\"_blank\" href=\"$link_for_more_infos\">Read more ></a>
              <hr>
              <div class='alert alert-relevance'>
                <h4>Relevance indicator</h4>
                <span>$relevance_percent</span>
                <div class='progress relevance-progressbar'>
                  <div class='progress-bar progressbar-relevance bg-success' role='progressbar' style='width:$relevance_percent%;' aria-valuenow='$relevance_result' aria-valuemin='0' aria-valuemax='100'></div>
                </div>
              </div>
            </div>

            <!-- Table of settings -->
            <div class=\"col-4\">
                <h3>Table of settings</h3>
                <hr>
                  <h4>UIX</h4>
                  $UIX_impact_content : <code>$UIX_impact</code>
                  <br>
                  <br>
                  $method_content
                  $registry_content
                  <h4>Values</h4>
                  <h6>Type : </h6><code>$type</code>
                  <h6>Possible Values : </h6>
                  <code>
                    <ul>
                      <li>$values</li>
                    </ul>
                  </code>
                  <div>
                  </div>
                <hr>
                $command_content
            </div>

            <!-- More Informations -->
            <div class=\"col-4\">
              <h3>More Informations</h3>
              <hr>
                $consequences_content
                $advice_content
                $notes_content
                $operating_system_content
            </div>

          </div>
          <!---- End data content ---->
        </div>
      </div>
    </td>
  </tr>
  <!---- End toggle content ---->";



}


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////  Main part ////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

$row = 0;
if (($handle = fopen($link_csv_file, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 3000, ",")) !== FALSE) {
        if ($row>0) {
          FileParser($data);
        }

        $row++;
    }
    fclose($handle);
}


////////////////////////////////////////////////////////////////////////////////
//////////////////////////////  Progress view //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

$policies_nbr = $row-1;
$done_policies_nbr = $policies_nbr-$done_policies_nbr;
$calc_global = intval($done_policies_nbr*100/$policies_nbr);
$calc_goal = intval($checked_policies_nbr*100/50);
$ALL_progress_view = "
<!---- Progress view ---->
<div class=\"alert alert-primary\" role=\"alert\" style=\"max-width:400px;\">
  <h4>Global progress - $done_policies_nbr/$policies_nbr</h4>
  <div class=\"progress\">
    <div class=\"progress-bar progress-bar-striped bg-success\" role=\"progressbar\" style=\"width: $calc_global%;\" aria-valuenow=\"$calc_global\" aria-valuemin=\"0\" aria-valuemax=\"100\">$calc_global%</div>
  </div>
  <h4>Goal progress - $checked_policies_nbr/50</h4>
  <div class=\"progress\">
    <div class=\"progress-bar progress-bar-striped bg-success\" role=\"progressbar\" style=\"width: $calc_goal%;\" aria-valuenow=\"$calc_goal\" aria-valuemin=\"0\" aria-valuemax=\"100\">$calc_goal%</div>
  </div>
</div>
<!---- End progress view ---->
";

$ALL_counter_view = "
<!---- Numbers view ---->
<div style=\"max-width:400px;\">
  <table class=\"table\" style=\"border: solid 1px rgb(227, 227, 227);\">
            <thead>
              <tr>
                <th></th>
                <th>All policies</th>
                <th>Checked policies</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>Mode</th>
                <td>mode 1</td>
                <td>mode 2</td>
              </tr>
              <tr>
                <th>High</th>
                <td>22</td>
                <td>18</td>
              </tr>
              <tr>
                <th>Medium</th>
                <td>22</td>
                <td>18</td>
              </tr>
              <tr>
                <th>Low</th>
                <td>22</td>
                <td>18</td>
              </tr>
              <tr>
                <th>No impact</th>
                <td>22</td>
                <td>18</td>
              </tr>
              <tr>
                <th>Potentially</th>
                <td>22</td>
                <td>18</td>
              </tr>
            </tbody>
    </table>
</div>
<!---- End Numbers view ---->
";


 ?>
