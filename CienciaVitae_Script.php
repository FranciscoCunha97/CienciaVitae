<?php

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Stream\Stream;


//connection to API CienciaVitae

$username = "UFP_ADMIN";
$password = "uR*r78M3m3B-D";
$remote_url = 'https://api.cienciavitae.pt/v1.1/searches/persons/institution?order=Ascending&pagination=true&rows=20&page=1&lang=PT';

$opts = array(
    'http'=> array(
        'method' => "GET",
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    )
);

$context = stream_context_create($opts);

$pagina = 1;
$api_url = 'https://api.cienciavitae.pt/v1.1/searches/persons/institution?order=Ascending&pagination=true&rows=20&page='.$pagina.'&lang=PT';

//connection to database
$serverName = "DESKTOP-BKFJB4D\\SQLEXPRESS";
$connectionInfo = array("Database"=>"CienciaVitae");
$conn = sqlsrv_connect($serverName,$connectionInfo);

if( $conn ) {
    echo "Connection established.<br />";
}else{
    echo "Connection could not be established.<br />";
    die( print_r( sqlsrv_errors(), true));
}

$query = "SELECT * FROM funcionarios WHERE CienciaVitaeID=?";
$query_all = "SELECT * FROM curriculos";
$query_insert_curriculo = "INSERT INTO curriculos(CienciaVitaeID,Cv,DataModificadaUpdate) VALUES (?,?,?)";
$query_update_data = "UPDATE funcionarios_up SET DataModifica_o=(?) WHERE CienciaVitaeID=?";

$options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
$params2 = array();
$stmt = sqlsrv_query($conn, $query_all, $params2, $options);

$nomeCompleto = 'NomeCompleto';
$idCiencia = 'CienciaVitaeID';

$cienciaId = " ";
$params = array(&$cienciaId,SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50));
$numeroIDs = sqlsrv_num_rows($stmt);


if($stmt = sqlsrv_prepare($conn, $query, $params)) {
    echo "Statement prepared" . "\n";
}else{
  echo "Statement could not be prepared" . "\n"; 
  die(print_r(sqlsrv_errors(), true));
}

/**
 * while para percorrer todas as paginas
 */
while($file = file_get_contents($api_url,false,$context)){
      $xml=simplexml_load_string($file) or die("Error: Cannot create object");
      $docentes = $xml->xpath('//search:search/search:result/person:person');

    foreach ($docentes as $docente) {
        $id = $docente->xpath('author-identifier:author-identifiers/author-identifier:author-identifier/author-identifier:identifier');
        $string_rec = strval($id[0]);
        $cienciaId = $string_rec;

        if(sqlsrv_execute($stmt)){
            $row = sqlsrv_fetch_array($stmt);      
            if($row === null){
              //echo $cienciaId . " nao encontrado \n";
            }else{
              //while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data = $docente->xpath('@common:last-modified-date');
                //$new_data= new DateTime($row['DataModifica_o']);
                //$result = $new_data->format('Y-m-d');
                //echo $row[$idCiencia] . " encontrou. data = " .$row["DataModifica_o"] . " XML_Data = " . $data[0] . "\n";
                $dataDB = $row['DataModifica_o'];
                $string_data = strval($data[0]);
                $subString_data = substr($string_data,0,10);
                $row_count = sqlsrv_num_rows($stmt);
                //echo "numero ------------ " . $row_count . "\n";
      
      
                //echo $dataDB . "-------------" . $string_data . "\n";
                //echo $dataDB . "\n";
                /*
                if($string_data >= $dataDB){
                  echo "dataXML -> " . $string_data. " --MAIOR-- " . "\n";
                }else{
                  echo "data db ->" . $dataDB . "--MAIOR" . "\n";
                }
                */
      
      
                    
                $remote_url2 = 'https://api.cienciavitae.pt/v1.1/curriculum/'. $row[$idCiencia];
                $context2 = stream_context_create($opts);
      
                $file2 = file_get_contents($remote_url2,false,$context2);
      
                //$xml2=simplexml_load_string($file2);
                //$cvs = $file2->xpath('//search:search/search:result/person:person');
      
                //echo $cvs;

                //$string_xml_cv = strval($cvs);
          if($subString_data <= $dataDB){
            //echo "dataXML -" . $subString_data . " menor ou igual que dataBaseDados - " . $dataDB . "\n";
            //echo "----" . "$numeroIDs" . "\n";
            if($numeroIDs < 140){
              
              $params_insert = array(
                array($row[$idCiencia],SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
                array($file2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR('max')),
                array($subString_data)
              );
             // echo $row[$idCiencia] . "inseriu" . "\n";
              $insert = sqlsrv_query($conn,$query_insert_curriculo,$params_insert);
              if($insert === false ) {
                die( print_r( sqlsrv_errors(), true));
              }
            }
            }else {
              $params_update = array($subString_data,$cienciaId);
             // echo $cienciaId . "--------atualizou" . "\n";
              $updateData = sqlsrv_query($conn,$query_update_data,$params_update);
              $params_insert3 = array(
                array($row[$idCiencia],SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
                array($file2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR('max')),
                array($string_data)
              );
              $insert3 = sqlsrv_query($conn,$query_insert_curriculo,$params_insert3);
              if($insert3 === false ) {
                die( print_r( sqlsrv_errors(), true));
              }
            }
            /**
             * fazer if do numero de ids da tabela nova e dentro o insert
             * ver na folha
             */
            /*
          }else{
            echo "dataXML -" . $subString_data . " maior que dataBaseDados - " . $dataDB . "\n";
            /**
             * fazer update e insert na tabela nova
             */ 
          }
  }else{
      die( print_r( sqlsrv_errors(), true));
    }
}$pagina = $pagina +1;
$api_url = 'https://api.cienciavitae.pt/v1.1/searches/persons/institution?order=Ascending&pagination=true&rows=20&page='.$pagina.'&lang=PT';

}
sqlsrv_close( $conn);
?>