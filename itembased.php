<?php
// || \\ array_push($data,['id_cuci' => $key->id_cuci,'nama_cuci' => $key->nama_cuci]);
    class ItemBased extends CI_Model {

        public function __construct() {
            parent::__construct();
            $this->load->model('ModelItem','mi');
        }

        //get the recommendation
        //mengambil rekomendasi
        public function getRecommendations($person,$item,$itemDatas,$datas){
            $sim = [];

            foreach ($itemDatas as $otherItem => $values) {
                if($otherItem != 'id_item-'.$item){
                    $temp = $this->similarities($itemDatas,$item,$otherItem,$person);
                    if (is_array($temp)){
                        $sim[$otherItem] = $temp;
                    }
                }
            }

            array_multisort($sim, SORT_DESC);

            $result = $this->setPrediction($itemDatas, $datas,$sim,$item,$person);
            return $result;
                
        }

        //setting prediction value each items
        //memberikan inlai prediksi terhadap item
        public function setPrediction($itemDatas, $datas,$sim,$cur_item,$person){
            $result = [];
            $sumSim = 0;
            $sumPre = 0;
            $check = 0;

            foreach ($datas as $item) {
                foreach ($itemDatas[$item->id_item] as $otherPerson => $value) {
                    $sumPre += ($sim["id_item-".$item->id_item][0] * $value[1]);
                    $sumSim += abs($sim["id_item-".$item->id_item][0]);
                    $check +=1;
                    //get rating prediction each data to only 5 higest score of user's similarity
                    //mengambil 5 data dari similaritas para user untuk menghitung prediksi score (nilai)
                    if($check > 5){
                        if ($sumSim<=0) 
                            $weight = 0;
                        else
                            $weight = $sumPre/$sumSim;

                            array_push($result,
                                [
                                    //list of returned data
                                    'weight' => $weight,
                                ]
                            );
                        break;
                    }
                }
                $sumPre = 0; $sumSim = 0; $check = 0;
            }

            array_multisort(array_column($result, 'rank'), SORT_DESC, $result);
            // print_r(array_column($result, 'id_item'));
            // die();
            return $result;
            // return $result;
        }

        //calculate similarity distance of rated data each users to user
        //menghitung jarak kesamaan dari rating yang dilakukan para pengguna terhadap pengguna tertentu
        public function similarities($itemDatas,$item,$otherItem,$person){
            $p_prsnAvg = 0;
            $o_prsnAvg = 0;
            $personItem = 0;
            $otherPersonItem = 0;
            $simNumerator = 0;

            //check similarity true or false
            //mengecek adanya kesamaan data
            if(!$this->isEmpty($itemDatas,$item,$otherItem,$person)){
               return 0;
            }

            //calculate similarities formula
            //menghitung rumun mencari kesamaan antar item
            foreach ($itemDatas[$item] as $otherPerson => $value) {
                if(array_key_exists($otherPerson, $itemDatas[$otherItem])){
                    if($otherPerson != $person){
                        $personItem += (pow((float)$value[1]-(float)$value[2],2)); 
                        $otherPersonItem += (pow((float)$itemDatas[$otherItem][$otherPerson][1]-(float)$itemDatas[$otherItem][$otherPerson][2],2));
                        $simNumerator += ((float)$value[1]-(float)$value[2])*((float)$itemDatas[$otherItem][$otherPerson][1]-(float)$itemDatas[$otherItem][$otherPerson][2]);
                    }
                }
            }

            $simDenominator = ((sqrt($personItem)) * (sqrt($otherPersonItem)));

            if ($simDenominator<=0){
               return 0;
            }
            else{
               return [($simNumerator / $simDenominator)];
            }
        }

        //check similarity true or false
        //mengecek adanya kesamaan data
        public function isEmpty($itemDatas,$item,$otherItem,$person){
            $count = 0;

            foreach ($itemDatas[$item] as $otherPerson => $value) {
                if(array_key_exists($otherPerson, $itemDatas[$otherItem])){
                    $count += 1;
                    return true;
                }
            }
            
            return false;
        }
    }

?>