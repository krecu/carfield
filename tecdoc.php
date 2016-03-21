<?php
$hostname = "";
$username = "";
$password = "";
$dbName = "tecdoc";

mysql_connect($hostname,$username,$password) OR DIE("Не могу создать соединение ");
mysql_select_db($dbName) or die(mysql_error());
mysql_query("SET NAMES 'utf8'");
mysql_query("SET CHARACTER SET 'utf8'");
mysql_query("SET SESSION collation_connection = 'utf8_general_ci'");

function tecdoc_get_languges(){
  $query = "
  SELECT
  LANGUAGES.LNG_ID,
  LANGUAGES.LNG_DES_ID,
  DESIGNATIONS.DES_ID,
  DESIGNATIONS.DES_LNG_ID,
  DESIGNATIONS.DES_TEX_ID,
  DES_TEXTS.TEX_ID,
  DES_TEXTS.TEX_TEXT
  FROM LANGUAGES
  INNER JOIN DESIGNATIONS ON DESIGNATIONS.DES_ID = LANGUAGES.LNG_DES_ID
  INNER JOIN DES_TEXTS ON DESIGNATIONS.DES_TEX_ID = DES_TEXTS.TEX_ID
  WHERE ((DESIGNATIONS.DES_LNG_ID = 16));";
  $result = mysql_query($query) or die(mysql_error());
  $output = '';
  $output .= '<table>';

  while ($row = mysql_fetch_array($result)) {
    $tr = '';
    $tr .= '<td>'.$row[0].'</td>';
    $tr .= '<td>'.$row[1].'</td>';
    $tr .= '<td>'.$row[2].'</td>';
    $output .= '<tr>'.$tr.'</tr>';
  }
  $output .= '</table>';

  return $output;
}

function tecdoc_get_types($ln){
  $query = "
  SELECT
  TYPES.TYP_KV_BODY_DES_ID,
  DES_TEXTS2.TEX_TEXT AS TYPE

  FROM TYPES

  INNER JOIN DESIGNATIONS AS DESIGNATIONS ON DESIGNATIONS.DES_ID = TYPES.TYP_KV_BODY_DES_ID
  INNER JOIN DES_TEXTS AS DES_TEXTS2 ON DESIGNATIONS.DES_TEX_ID = DES_TEXTS2.TEX_ID

  WHERE DESIGNATIONS.DES_LNG_ID = ".$ln."
  GROUP BY TYPES.TYP_KV_BODY_DES_ID";

  $result = mysql_query($query) or die(mysql_error());

  $data = array();
  while ($row = mysql_fetch_array($result)) {
    $data[$row[0]] = array(
      'id' => $row[0],
      'name' => $row[1],
    );
  }

  return $data;
}

function tecdoc_get_manufactures(){
  $query = "
  SELECT
  MFA_ID,
  MFA_BRAND,
  MFA_PC_MFC
  FROM MANUFACTURERS ORDER BY MFA_BRAND";
  $result = mysql_query($query) or die(mysql_error());

  $data = array();
  while ($row = mysql_fetch_array($result)) {
    $data[$row[0]] = array(
      'id' => $row[0],
      'name' => $row[1],
    );
  }
  return $data;
}

function tecdoc_get_models($id, $ln){
  $query = "
  SELECT
  MODELS.MOD_ID AS ID,
  MODELS.MOD_MFA_ID AS MANUFACTURE_ID,
  MODELS.MOD_CDS_ID AS MODEL_ID,
  MODELS.MOD_PCON_START AS MODEL_DATE_START,
  MODELS.MOD_PCON_END AS MODEL_DATE_END,
  DES_TEXTS.TEX_TEXT AS MODELNAME

  FROM MODELS

  INNER JOIN COUNTRY_DESIGNATIONS ON COUNTRY_DESIGNATIONS.CDS_ID = MODELS.MOD_CDS_ID
  INNER JOIN DES_TEXTS ON COUNTRY_DESIGNATIONS.CDS_TEX_ID = DES_TEXTS.TEX_ID

  WHERE (MODELS.MOD_MFA_ID = ".$id." AND COUNTRY_DESIGNATIONS.CDS_LNG_ID = ".$ln." )
  GROUP BY MODELS.MOD_ID
  ORDER BY DES_TEXTS.TEX_TEXT";
  $result = mysql_query($query) or die(mysql_error());
  $data = array();
  while ($row = mysql_fetch_array($result)) {
    $data [] = array(
      'id' => $row[0],
      'date_start' => $row[3],
      'date_end' => $row[4],
      'name' => $row[5],
    );
  }
  return $data;
}

function tecdoc_get_modification($id, $ln){
  $query = "
  SELECT
  TYPES.TYP_ID AS ID,
  TYPES.TYP_CDS_ID,
  TYPES.TYP_MMT_CDS_ID,
  TYPES.TYP_MOD_ID,
  TYPES.TYP_KV_BODY_DES_ID,
  DES_TEXTS.TEX_TEXT AS FULLNAME,
  DES_TEXTS1.TEX_TEXT AS SHORTNAME,
  DES_TEXTS2.TEX_TEXT AS TYPE

  FROM TYPES

  INNER JOIN COUNTRY_DESIGNATIONS ON COUNTRY_DESIGNATIONS.CDS_ID = TYPES.TYP_MMT_CDS_ID
  INNER JOIN DES_TEXTS ON COUNTRY_DESIGNATIONS.CDS_TEX_ID = DES_TEXTS.TEX_ID

  INNER JOIN COUNTRY_DESIGNATIONS AS COUNTRY_DESIGNATIONS1 ON COUNTRY_DESIGNATIONS1.CDS_ID = TYPES.TYP_CDS_ID
  INNER JOIN DES_TEXTS AS DES_TEXTS1 ON COUNTRY_DESIGNATIONS1.CDS_TEX_ID = DES_TEXTS1.TEX_ID

  INNER JOIN DESIGNATIONS AS DESIGNATIONS ON DESIGNATIONS.DES_ID = TYPES.TYP_KV_BODY_DES_ID
  INNER JOIN DES_TEXTS AS DES_TEXTS2 ON DESIGNATIONS.DES_TEX_ID = DES_TEXTS2.TEX_ID


  WHERE TYPES.TYP_MOD_ID = '".$id."'
  AND COUNTRY_DESIGNATIONS.CDS_LNG_ID = ".$ln."
  AND COUNTRY_DESIGNATIONS1.CDS_LNG_ID = ".$ln."
  AND DESIGNATIONS.DES_LNG_ID = ".$ln."
  GROUP BY TYPES.TYP_ID
  ORDER BY TYPES.TYP_SORT";

  $result = mysql_query($query) or die(mysql_error());

  $data = array();
  while ($row = mysql_fetch_array($result)) {
    $data[$row[0]] = array(
      'id' => $row[0],
      'name' => $row[5],
      'short' => $row[6],
      'type' => $row[4],
    );
  }

  return $data;
}


if (isset($_GET['q'])) {

  $ln = 4;
  if (isset($_GET['ln'])) {
    $ln = $_GET['ln'];
  }

  switch ($_GET['q']) {
    case 'manufactures' :
      header('Content-type: application/json');
      $manufactures = tecdoc_get_manufactures();
      print json_encode($manufactures);
      exit;
      break;
    case 'models' :
      header('Content-type: application/json');
      if (isset($_GET['manufactures'])) {
        $models = tecdoc_get_models($_GET['manufactures'], $ln);
        print json_encode($models);
      }
      exit;
      break;
    case 'types' :
      header('Content-type: application/json; charset=utf-8');
      $types = tecdoc_get_types($ln);

      print "<pre>";
      print_r($types);
      print "</pre>";

      print json_encode($types);
      exit;
      break;
    case 'modifications' :
      header('Content-type: application/json');
      if (isset($_GET['model'])) {
        $modification = tecdoc_get_modification($_GET['model'], $ln);
        print json_encode($modification);
      }
      exit;
      break;
    case 'ex' :
      // get all manufactures

      $all_model = 0;
      $all_modification = 0;

      $manufactures = file_get_contents("http://daway.com/test.php?q=manufactures&ln=16");
      $manufactures = json_decode($manufactures);
      foreach ($manufactures as $manufacture) {
        print '<h1 style="font-size: 14;">ID='.$manufacture->id.'; name='.$manufacture->name.'</h1>';
        // get models
        $models = file_get_contents("http://daway.com/test.php?q=models&manufactures=".$manufacture->id."&ln=16");
        $models = json_decode($models);
        print '<h2 style="font-size: 12; padding-left: 50px">ALL MODEL='.count($models).'</h2>';
        $all_model += count($models);
        foreach ($models as $model) {
          //print '<h2 style="font-size: 12; padding-left: 50px">ID='.$model->id.'; name='.$model->name.' date='.$model->date_start.'|'.$model->date_end.'</h2>';
          // get modification
          $modifications = file_get_contents("http://daway.com/test.php?q=modifications&model=".$model->id."&ln=16");
          $modifications = json_decode($modifications);
          print '<h2 style="font-size: 12; padding-left: 50px">ALL MOD='.count($modifications).'</h2>';
          $all_modification += count($modifications);
          foreach ($modifications as $modification) {
            //print '<h3 style="font-size: 10; padding-left: 100px">ID='.$modification->id.'; name='.$modification->name.' short='.$modification->type.'</h3>';
          }
        }

        print '<hr>';
        print "All vendors = ".count($manufactures).'<br>';
        print "All models = ".$all_model.'<br>';
        print "All modifications = ".$all_modification.'<br>';
      }
      break;
  }
}



mysql_close();
