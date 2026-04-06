<?php
include_once("../../database/connection.php");

class Reports extends Database
{
    public function ListModules(){
        $mods = $this->SelectQuery("SELECT id_module, module FROM tbl_maintenance_module WHERE module_type = 0 ORDER BY module ASC");
        echo json_encode(array("MODULES"=>$mods));
    }

    public function GetHierarchy(){
        $hier = array();
        // CALLS SELECTQUERY HERE
        $modules = $this->SelectQuery("SELECT id_module, module FROM tbl_maintenance_module WHERE module_type = 0 ORDER BY module ASC");
        
        foreach ($modules as $m) {
            $mid = intval($m["id_module"]);
            $submodules = $this->SelectQuery("SELECT id_module, module FROM tbl_maintenance_module WHERE module_type = 1 AND module_no = ".$mid." ORDER BY module ASC");
            $itemsUnderModule = $this->SelectQuery("SELECT id_module, module FROM tbl_maintenance_module WHERE module_type = 2 AND module_no = ".$mid." ORDER BY module ASC");
            
            $subs = array();
            foreach ($submodules as $s) {
                $sid = intval($s["id_module"]);
                $itemsUnderSub = $this->SelectQuery("SELECT id_module, module FROM tbl_maintenance_module WHERE module_type = 2 AND module_no = ".$sid." ORDER BY module ASC");
                $subs[] = array(
                    "id_module"=>$sid,
                    "module"=>$s["module"],
                    "module_type"=>1,
                    "items"=>$itemsUnderSub
                );
            }
            $hier[] = array(
                "id_module"=>$mid,
                "module"=>$m["module"],
                "module_type"=>0,
                "items"=>$itemsUnderModule,
                "submodules"=>$subs
            );
        }
        echo json_encode(array("HIERARCHY"=>$hier));
    }

    // --- ADDED THIS FUNCTION TO FIX THE FATAL ERROR ---
    private function SelectQuery($string){
        $data = [];
        $result = $this->conn->query($string);
        if($result && $result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
        }
        return $data;
    }
}
?>