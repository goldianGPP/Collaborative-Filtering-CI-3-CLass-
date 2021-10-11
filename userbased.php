<?php
// || \\ array_push($data,['id_cuci' => $key->id_cuci,'nama_cuci' => $key->nama_cuci]);
    class UserBased extends CI_Model {

        public function __construct() {
            parent::__construct();
            $this->load->model('ModelItem','mi');
        }

        //get the recommendation
        //mengambil rekomendasi
        public function getRecommendations($person,$personDatas,$datas){
            $sim = [];

            foreach ($personDatas as $otherPerson => $values) {
                if($otherPerson != $person){
                    $temp = $this->similarities($personDatas,$person,$otherPerson);
                    if (is_array($temp)) {
                        $sim[$otherPerson] = $temp;
                    }
                }
            }

            array_multisort($sim, SORT_DESC);

            return $this->setPrediction($personDatas,$datas,$sim,$person);
        }

        public function setPrediction($personDatas, $datas,$sim,$person){
            $result = [];
            $sumSim = 0;
            $sumPre = 0;
            $check = 0;
            $avgU= 0;

            foreach ($personDatas[$person] as $item => $value) {
                $avgU = $value[2];
                break;
            }

            foreach ($datas as $item) {
                foreach ($sim as $otherPerson => $value) {
                    if(array_key_exists($item->id_item, $personDatas[$otherPerson])){
                        $sumPre += ($value[0] * $value[1]);
                        $sumSim += abs($value[0]);
                        $check += 1;

                        if($check >= 5)
                            break;
                    }
                }
                if($check != 0){
                    $weight = $avgU + ($sumPre/$sumSim);
                    array_push($result,
                        [
                            //list of returned data
                            'weight' => $weight,
                        ]
                    );
                }
                $sumPre = 0; $sumSim = 0; $check = 0;
            }
            
            array_multisort(array_column($result, 'rank'), SORT_DESC, $result);
            return $result;
        }

        //array_multisort(array_column($inventory, 'price'), SORT_DESC, $inventory);

        //calculate similarity distance of rated data each users to user
        //menghitung jarak kesamaan dari rating yang dilakukan para pengguna terhadap pengguna tertentu
        public function similarities($personDatas,$person,$otherPerson){
            $o_prsnAvg = 0;
            $personItem = 0;
            $otherPersonItem = 0;
            $simNumerator = 0;

            if(!$this->isEmpty($personDatas,$person,$otherPerson)){
                return 0;
            }

            //calculate similarities formula
            //menghitung rumun mencari kesamaan antar user
            foreach ($personDatas[$person] as $item => $value) {
                if(array_key_exists($item, $personDatas[$otherPerson])){
                    $personItem += (pow((float)$value[1]-(float)$value[2],2)); 
                    $otherPersonItem += (pow((float)$personDatas[$otherPerson][$item][1]-(float)$personDatas[$otherPerson][$item][2],2));
                    $o_prsnAvg += ((float)$personDatas[$otherPerson][$item][1]-(float)$personDatas[$otherPerson][$item][2]);
                    $simNumerator += ((float)$value[1]-(float)$value[2])*((float)$personDatas[$otherPerson][$item][1]-(float)$personDatas[$otherPerson][$item][2]);
                }
            }

            $Vdistance = $o_prsnAvg;

            $simDenominator = (sqrt($personItem) * sqrt($otherPersonItem));
            if ($simDenominator<0) {
                return 0;
            }
            else
                return [($simNumerator / $simDenominator), $Vdistance];
        }

        //check similarity true or false
        //mengecek adanya kesamaan data
        public function isEmpty($personDatas,$person,$otherPerson){
            foreach ($personDatas[$person] as $item => $value) {
                if(array_key_exists($item, $personDatas[$otherPerson])){
                    return true;
                }
            }

            return false;
        }
    }

?>