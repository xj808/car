<?php
namespace geo;
class Geo
{
    private $coding="0123456789bcdefghjkmnpqrstuvwxyz";
    private $codingMap=array();

    public function Geohash()
    {
        //build map from encoding char to 0 padded bitfield
        for($i=0; $i<32; $i++)
        {
            $this->codingMap[substr($this->coding,$i,1)]=str_pad(decbin($i), 5, "0", STR_PAD_LEFT);
        }

    }

    /**
     * Decode a geohash and return an array with decimal lat,long in it
     */
    public function decode_hash($hash)
    {
        //decode hash into binary string
        $binary="";
        $hl=strlen($hash);
        for($i=0; $i<$hl; $i++)
        {
            $binary.=$this->codingMap[substr($hash,$i,1)];
        }

        //split the binary into lat and log binary strings
        $bl=strlen($binary);
        $blat="";
        $blong="";
        for ($i=0; $i<$bl; $i++)
        {
            if ($i%2)
                $blat=$blat.substr($binary,$i,1);
            else
                $blong=$blong.substr($binary,$i,1);

        }

        //now concert to decimal
        $lat=$this->binDecode($blat,-90,90);
        $long=$this->binDecode($blong,-180,180);

        //figure out how precise the bit count makes this calculation
        $latErr=$this->calcError(strlen($blat),-90,90);
        $longErr=$this->calcError(strlen($blong),-180,180);

        //how many decimal places should we use? There's a little art to
        //this to ensure I get the same roundings as geohash.org
        $latPlaces=max(1, -round(log10($latErr))) - 1;
        $longPlaces=max(1, -round(log10($longErr))) - 1;

        //round it
        $lat=round($lat, $latPlaces);
        $long=round($long, $longPlaces);

        return array($lat,$long);
    }


    /**
     * Encode a hash from given lat and long
     */
    public function encode_hash($lat,$long)
    {
        //how many bits does latitude need?
        $plat=$this->precision($lat);
        $latbits=1;
        $err=45;
        while($err>$plat)
        {
            $latbits++;
            $err/=2;
        }

        //how many bits does longitude need?
        $plong=$this->precision($long);
        $longbits=1;
        $err=90;
        while($err>$plong)
        {
            $longbits++;
            $err/=2;
        }

        //bit counts need to be equal
        $bits=max($latbits,$longbits);

        //as the hash create bits in groups of 5, lets not
        //waste any bits - lets bulk it up to a multiple of 5
        //and favour the longitude for any odd bits
        $longbits=$bits;
        $latbits=$bits;
        $addlong=1;
        while (($longbits+$latbits)%5 != 0)
        {
            $longbits+=$addlong;
            $latbits+=!$addlong;
            $addlong=!$addlong;
        }


        //encode each as binary string
        $blat=$this->binEncode($lat,-90,90, $latbits);
        $blong=$this->binEncode($long,-180,180,$longbits);

        //merge lat and long together
        $binary="";
        $uselong=1;
        while (strlen($blat)+strlen($blong))
        {
            if ($uselong)
            {
                $binary=$binary.substr($blong,0,1);
                $blong=substr($blong,1);
            }
            else
            {
                $binary=$binary.substr($blat,0,1);
                $blat=substr($blat,1);
            }
            $uselong=!$uselong;
        }

        //convert binary string to hash
        $hash="";
        for ($i=0; $i<strlen($binary); $i+=5)
        {
            $n=bindec(substr($binary,$i,5));
            $hash=$hash.$this->coding[$n];
        }


        return $hash;
    }

    /**
     * What's the maximum error for $bits bits covering a range $min to $max
     */
    private function calcError($bits,$min,$max)
    {
        $err=($max-$min)/2;
        while ($bits--)
            $err/=2;
        return $err;
    }

    /*
    * returns precision of number
    * precision of 42 is 0.5
    * precision of 42.4 is 0.05
    * precision of 42.41 is 0.005 etc
    */
    private function precision($number)
    {
        $precision=0;
        $pt=strpos($number,'.');
        if ($pt!==false)
        {
            $precision=-(strlen($number)-$pt-1);
        }
        return pow(10,$precision)/2;
    }


    /**
     * create binary encoding of number as detailed in http://en.wikipedia.org/wiki/Geohash#Example
     * removing the tail recursion is left an exercise for the reader
     */
    private function binEncode($number, $min, $max, $bitcount)
    {
        if ($bitcount==0)
            return "";

        #echo "$bitcount: $min $max<br>";

        //this is our mid point - we will produce a bit to say
        //whether $number is above or below this mid point
        $mid=($min+$max)/2;
        if ($number>$mid)
            return "1".$this->binEncode($number, $mid, $max,$bitcount-1);
        else
            return "0".$this->binEncode($number, $min, $mid,$bitcount-1);
    }


    /**
     * decodes binary encoding of number as detailed in http://en.wikipedia.org/wiki/Geohash#Example
     * removing the tail recursion is left an exercise for the reader
     */
    private function binDecode($binary, $min, $max)
    {
        $mid=($min+$max)/2;

        if (strlen($binary)==0)
            return $mid;

        $bit=substr($binary,0,1);
        $binary=substr($binary,1);

        if ($bit==1)
            return $this->binDecode($binary, $mid, $max);
        else
            return $this->binDecode($binary, $min, $mid);
    }

    //根据有lat_lon的二维数组,获取距离并根据距离排序
    function sortByDistance($rs_arr,$lat1,$lng1,$scope)
    {
        foreach ($rs_arr as $k => $v) {
            $lat2 = $v['lat'];
            $lng2 = $v['lng'];
            $rs_arr[$k]['distance'] = $this->getDistance($lat1, $lng1, $lat2, $lng2);
        }
        /*未处理距离筛选加排序合到一块*/
        if($scope != '') $rs_arr = $this->get_scope($rs_arr,$scope);
        $rs_arr = self::quick_sort_arr($rs_arr,'distance');
        return $rs_arr;
    }

    //获得两点距离
    public function getDistance($lat1,$lng1,$lat2,$lng2)
    {
        //地球半径
        $R = 6370996.81;
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $s = acos(cos($radLat1)*cos($radLat2)*cos($radLng1-$radLng2)+sin($radLat1)*sin($radLat2))*$R;
        return  round(($s)/1000,2);//返回公里数
    }

    //根据距离筛选
    private function get_scope($list1,$scope){
        $list2 = array();
        foreach($list1 as $k=>$v){
            if($v['distance'] <$scope){
                $list2[] = $v;
            }
        }
        return $list2;
    }
    private static function quick_sort_arr($rs_arr,$file){
        $count = count($rs_arr);
        if($count <= 1) return $rs_arr;
        $left_arr = array();
        $right_arr = array();
        for ($i=1;$i<$count;$i++){
            if($rs_arr[$i][$file] <=$rs_arr[0][$file]){
                $left_arr[] = $rs_arr[$i];
            }else{
                $right_arr[] = $rs_arr[$i];
            }
        }
        $left_arr = self::quick_sort_arr($left_arr,$file);
        $right_arr = self::quick_sort_arr($right_arr,$file);
        return array_merge($left_arr,array($rs_arr[0]),$right_arr);
    }
}