<?php
defined('BASEPATH') or exit('No direct script access allowed');
error_reporting(1);

class Ups_query extends CI_Controller{
    public function get_scan_dates(){
        $this->load->database();
        //1. Veritabanından verileri çekmek
        //echo "Veritabanindan verileri başarılı bir şekilde çekildi<br>";
        $this->db->select("o.*,u.id");
        $this->db->join("ups_scan_dates u","u.order_id=o.order_id","LEFT");
        $this->db->where("o.carrier","ups");//ordersdaki ups carrierleri getir
        $this->db->where("u.id IS NULL");//ilk parametre düzenlenecek veri ikinci parametre ise id. Boş ise veri gelsin dolu ise gelmesin
        $this->db->limit(10);
        $orders = $this->db->get("orders o")->result();
        // exit();\admin_se3434
        //2. Çekilen verilerin takip numarasının sorgulanması
        foreach ($orders as $order) {
            $s_data = $this->track_ups($order->tracking_number);
            //  echo $s_data."<br>";
            //  exit;
            //3. gelen verinin parse işlemleri
            $control_date = $this->parser($s_data);
            // echo "Origin date : ".$control_date."<br>";
            //4. ups_scan_dates tablosuna insert işlemi
            if($control_date == "error"){
                $mode = "a+";
                $text = " Tarihli hata bilgisi: "."\n" . $control_date ." "."Sipariş Numarası: "." ".$order->order_id ." "."Takip numarası: ". " " . $order->tracking_number ." ". "Bilgisine ait veri için date bilgisi alınamadı<br>";
                $file_name = "get_scan_dates.txt";
                $file = fopen(__DIR__ ."/logs/$file_name", $mode);
                fwrite($file, $text);
                fclose($file);
                echo $order->order_id." Numaralı order id için date bilgisi alınamadı<br>";
            }else{
                //veritabanı işlemleri
                // $this->get_scan_dates($order); // ??
                $this->db->select("u.id");
                $this->db->where("u.order_id",$order->order_id);
                $row = $this->db->get("ups_scan_dates u")->row();
                if($row){
                    echo $row->id." numarasına ait veri birden fazla eklenemez<br>";
                }else{
                    $insert_arr = array();
                    $order_id_data = $insert_arr["order_id"]=$order->order_id;
                    $tracking_number_data = $insert_arr["tracking_number"]=$order->tracking_number;
                    $scan_date_data = $insert_arr["scan_date"]=$control_date;
                    $insert_data = $this->db->insert("ups_scan_dates",$insert_arr);
                    $insert_id = $this->db->insert_id();
                    echo $insert_id. " id'sine  ait veri kaydedildi<br>";
                    echo "Sipariş Numarası: ".$order_id_data."<br>";
                    echo "Scan Tarih: ".$control_date."<br>";
                    echo "Takip Numarası: ".$tracking_number_data."<br><br>";
                }
            }
        }
    }
    public function parser($string_data){
        $return_data = "error";
        try {
            //echo $string_data."<br><br>";
            $json = json_decode($string_data);
            // vardump($json);
            //propert_exist ile belirtilern özelliğe sahip olup olmadığını kontrol eder.
            //obje
            if(is_object($json) && property_exists($json,"TrackResponse") && property_exists($json->TrackResponse,"Shipment") && property_exists($json->TrackResponse->Shipment,"Package")
                &&  property_exists($json->TrackResponse->Shipment->Package,"Activity")   ){
                if(is_object($json->TrackResponse->Shipment->Package->Activity)){
                    echo "Type: Object <br>";
                    //  echo "Activity: ".json_encode($activity)."<br>";
                    if (property_exists($json->TrackResponse->Shipment->Package->Activity,"Date")  && $json->TrackResponse->Shipment->Package->Activity->Date) {
                        $return_data = date("Y-m-d H:i:s",strtotime($json->TrackResponse->Shipment->Package->Activity->Date." ".$json->TrackResponse->Shipment->Package->Activity->Time));
                    }
                }elseif(is_array($json->TrackResponse->Shipment->Package->Activity)){
                    echo "Type: Array <br>";
                    foreach ($json->TrackResponse->Shipment->Package->Activity as $activity) {
                        // echo "Activity: ".json_encode($activity)."<br>";
                        if($activity->Status->Description == "Origin Scan"){
                            $return_data = date("Y-m-d H:i:s",strtotime($activity->Date." ".$activity->Time));
                        }
                    }
                }else{
                    $mode = "a+";
                    $text = $return_data."Obje Parser işleminde hata var";
                    $file_name = "get_scan_dates.txt";
                    $file = fopen(__DIR__ ."/logs/$file_name", $mode);
                    fwrite($file, $text);
                    fclose($file);
                    echo " parser metodundaki(is_obejct($json)) işlemlerinde hata var";
                }
                //array
            }elseif(is_array($json) && array_key_exists($json,"TrackResponse") && array_key_exists($json->TrackResponse,"Shipment")  && array_key_exists($json->TrackResponse->Shipment,"Package"
                    &&  array_key_exists($json->TrackResponse->Shipment->Package,"Activity"))){
                if(is_object($json->TrackResponse->Shipment->Package->Activity)){
                    echo "Type: Object <br>";
                    //  echo "Activity: ".json_encode($activity)."<br>";
                    if (property_exists($json->TrackResponse->Shipment->Package->Activity,"Date")  && $json->TrackResponse->Shipment->Package->Activity->Date) {
                        $return_data = date("Y-m-d H:i:s",strtotime($json->TrackResponse->Shipment->Package->Activity->Date." ".$json->TrackResponse->Shipment->Package->Activity->Time));
                    }
                }elseif(is_array($json->TrackResponse->Shipment->Package->Activity)){
                    echo "Type: Array <br>";
                    foreach ($json->TrackResponse->Shipment->Package->Activity as $activity) {
                        // echo "Activity: ".json_encode($activity)."<br>";
                        if($activity->Status->Description == "Origin Scan"){
                            $return_data = date("Y-m-d H:i:s",strtotime($activity->Date." ".$activity->Time));
                        }
                    }
                }else{
                    $mode = "a+";
                    $text = $return_data."Array Parser işleminde hata var";
                    $file_name = "get_scan_dates.txt";
                    $file = fopen(__DIR__ ."/logs/$file_name", $mode);
                    fwrite($file, $text);
                    fclose($file);
                    echo " parser metodundaki(is_obejct($json)) işlemlerinde hata var";
                }
            }else{
                echo gettype($json);
                $this->log->file_log();
                //var_dump($json);
                echo "hata kodu 5.<br>";
            }
        } catch (\Throwable $th) {
            $this->file_log($th);
            echo $th."<br>";
        }
        return $return_data;
    }
    public function file_log(){
        $mode = "a+";
        $text = "\n".date("Y-m-d H:i:s");
        $file_name = "get_scan_dates.txt";
        $file = fopen(__DIR__ ."/logs/$file_name", $mode);
        fwrite($file, $text);
        fclose($file);
    }

    public function track_ups($tracking_number)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apitracker.shipentegra.com/Shipment_tracking/get_ups/'.$tracking_number,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
?>
