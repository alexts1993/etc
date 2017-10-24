<?php
class common_model extends CI_Model {

    public $table = '';
    public $protected_fields = 'pwd';   //fields to be blocked from selecting
    public $protection_switch = true;   //whether or not to return data of $protected_fields columns;
    public $debug = false;
    public function __construct() {
        $this->load->database();
    }

    public function find($condition = array(),$fields = '*', $limit = '', $orderby = array(),$table = ''){
        return $this->from($table)->condition($condition)->fields($fields)->limit($limit)->order_by($orderby)->get_one();
    }

    public function search($condition = array(),$fields = '*', $limit = '', $orderby = array(),$table = ''){
        return $this->from($table)->condition($condition)->fields($fields)->limit($limit)->order_by($orderby)->select();
    }

    public function num_of_row($condition = array(),$table = ''){
        return $this->condition($condition)->count($table);
    }

    public function delete_row($condition = array(),$limit = NULL, $table = ''){
        $re = $this->from($table)->condition($condition)->delete('',$limit,$table);
        return $re == true;
    }

    public function update_row($update,$condition = array(),$table = ''){
        $table = $table?$table:$this->table;
        return $this->from($table)->condition($condition)->update($table,$update);
    }

    public function insert_row($data,$table = ''){
        if($this->debug){
            $e = $this->get_debug_msg('insert');
            $this->show_error($e);
        }
        $table = $table?$table:$this->table;
        return $this->db->insert($table,$data);
    }


    function update($table,$update){
        if($this->debug){
            $e = $this->get_debug_msg('update');
            $this->show_error($e);
        }
        return $this->db->update($table,$update);
    }


    public function fields($fields = '',$table = ''){
        $table = $table?$table:$this->table;
        $a = $this->_list_fields($table);
        if($fields[0] == '*'){
            $f = implode(',',$a);
        }elseif($fields[0] == '+'){
            $b = explode(',', substr($fields,1));
            $f = array_intersect($b,$a);
        }elseif($fields[0] == '-'){
            $b = explode(',', substr($fields,1));
            $f = array_diff($b,$a);
        }else{
            $f = implode(',',$a);
        }
        return $this->select_fields($f);
    }

    public function select_fields($fields){
        $f = is_array($fields)?implode(',',$fields):$fields;
        $this->db->select($f);//the return of instance itself is mainly for method chaining;
        return $this;
    }

    public function get_one(){
        if($this->debug){
            $e = $this->get_debug_msg('select');
            $this->show_error($e);
        }
        return  $this->db->get()->row_array();
    }

    public function select(){
        if($this->debug){
            $e = $this->get_debug_msg('select');
            $this->show_error($e);
        }
        return $this->db->get()->result_array();
    }

    // public function insert($data,$table = ''){
    //     return $this->db->insert($table,$data);
    // }

    /* get the number of rows in a table
     * @return type int
     */
    public function count($table = ''){
        if($this->debug){
            $e = $this->get_debug_msg('select');
            $this->show_error($e);
        }
        $table = $table?$table:$this->table;
        return $this->db->count_all_results($table);
    }

    public function delete($condition = array(),$table = '',$limit = NULL){
        if($this->debug){
            $e = $this->get_debug_msg('delete');
            $this->show_error($e);
        }
        $table = $table?$table:$this->table;
        $this->db->delete($table,$condition,$limit);
    }

    public function get_debug_msg($type = 'select'){
        $a = ['select','insert','update','delete'];
        $type = in_array($type,$a)?$type:false;
        if($type == false){
            return 'Error!';
        }
        $func = 'get_compiled_' . $type;
        return $this->db->$func();
    }

    public function show_error($msg = '',$heading = '',$data = array()){
        $data['heading'] = $heading?$heading:'Database Debugging Mode';
        $data['message']= $msg;
        $this->load->library('parser');
        $page = $this->parser->parse('errors/html/error_db', $data,true);
        echo $page;
        exit();
    }

    //use table
    public function from($table = ''){
        $table = $table?$table:$this->table;
         $this->db->from($table);
         return $this;
    }

    public function limit($data = array()){
        if($data){
            $data = explode(',', $data);
            $this->db->limit($data[0],$data[1]);
        }
        return $this;
    }

    public function order_by($data){
        $dirs = array('ASC','DESC','RANDOM');
        if($data){
            foreach($data as $o){
                if(in_array(strtoupper($o['dir']),$dirs)){
                    $this->db->order_by($o['field'], $o['dir']);
                }
            }
        }
        return $this;
    }

    public function group_by($data){
        if($data){
            $this->db->group_by($data);
        }
        return $this;
    }

    public function condition($condition = array()){
        if($condition){
            foreach($condition as $cd){
                $func = '__' . $cd['op'];
                $this->$func($cd['data']);
            }
        }
        return $this;
    }

    public function __where($condition){
        $this->db->where($condition['field'],$condition['value']);
    }

    public function __or_where($condition){
        $this->db->or_where($condition['field'],$condition['value']);
    }

    public function __like($condition){
        $sides = array('before','both','after');
        $this->db->like($condition['field'],$condition['match'],$condition['side']);
    }

    public function __where_in($condition){
        $this->db->where_in($condition['field'],$condition['values']);
    }


    /* list fields' names in a table
     * @return type array
     */
    public function _list_fields($table = ''){
        $table = $table?$table:$this->table;
        return $this->db->list_fields($table);
    }

    //get Database version
    public function _version(){
        return $this->db->version();
    }

    /* determine if a specific table exists.
     * @return type boolean
     */
    public function _table_exists($table = ''){
        $table = $table?$table:$this->table;
        return $this->db->table_exists($table);
    }

    /* determine if a specific field exists in a specific table
     * @return type boolean
     */
    public function _field_exists($field = '',$table = ''){
        $table = $table?$table:$this->table;
        return $this->db->field_exists($field,$table);
    }

    /* Gets a list containing field data about a table.
     * @return type array
     */
    public function _field_data($table = ''){
        $table = $table?$table:$this->table;
        $re =  $this->db->field_data($table);
        foreach($re as $k=>$v){
            $re[$k] = (array)$v;
        }
        return $re;
    }

    /* Gets a list of the tables in the current database.
     * @return type array
     */
    public function _list_tables(){
        return $this->db->list_tables();
    }

}
