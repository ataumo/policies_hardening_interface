
<!--file that will be read in interface (ux list)-->
<?php $link_csv_file = "finding_list_msft_security_baseline_microsoft_365_apps_v2104_machine.csv"; ?>
<!--file that will be read in to generate csv file (original list)-->
<?php $link_csv_original_file = "finding_list_msft_security_baseline_microsoft_365_apps_v2104_machine.csv"; ?>

<?php
$reference_name = "Microsoft Security Baseline"; //* necessary value
$reference_date = "To ckeck";       // optional
$reference_version = "Final"; // optional
$reference_type = "machine";    // optional
$reference_source_url = "";
 ?>

<!-- All links -->
<?php include($_SERVER['DOCUMENT_ROOT']."/policies_hardening_interface/ressources/links.php");?>

<!DOCTYPE html>
<?php include($link_motor_csv_reader);?>
