<?php
class ModelFormsMyform extends Model
{
    public function saveData($data_array)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "myforms SET first_value='".
        $this->db->escape($data_array['first_value'])."' ,second_value='".
        $this->db->escape($data_array['second_value'])."',third_value='".$this->db->escape($data_array['third_value'])."',forth_value='".$this->db->escape($data_array['forth_value'])."',date_added=NOW() ");

        $form_id = $this->db->getLastId();
        return $form_id;
    }
}
?>