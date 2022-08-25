<?php
// Loading table class
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class Table_Wrong_Url extends WP_List_Table
{
	private $wpdb;
	public function __construct()
	{
		parent::__construct();
		global $wpdb;
    	$this->wpdb = $wpdb;
	}

	public function get_columns()
	{
		return array(
			'url' => __('url'),
			'estado' => __('estado'),
			'origen' => __('origen')
		);
	}

	public function get_Data()
	{
		$table_name = $this->wpdb->prefix . 'wrong_url';
    	return $this->wpdb->get_results("SELECT url, estado, origen FROM {$table_name}", ARRAY_A);
	}

	public function prepare_items()
	{
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->get_Data();
  	}

  	function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'url':
				return $item[$column_name];
			case 'estado':
				return $item[$column_name];
			case 'origen':
				return "<a href=".get_edit_post_link($item[$column_name]).">".get_post($item[$column_name])->post_title."</a>";
		}
	}
}