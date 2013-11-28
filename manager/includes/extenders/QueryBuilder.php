<?php
/**
 * Created by fedo (fresh)
 * Date: 06.11.13
 * Time: 17:10
 * Класс производит построение сложных запросов выбирающих данные для дальнейшего построения сущности документа Modx
 * Класс не производит запросов к БД!
 */
 
class QueryBuilder {
	public	$search_fields=array('longtitle', 'introtext', 'content'),
			$sql,
			$config;


	/**
	 * @param $s_db_name - имя БД
	 * @param $s_db_prefix - префикс
	 * Конструктор
	 */
	public function __construct($s_db_name, $s_db_prefix){

		$this->config = array(
			'dbase' => $s_db_name,
			'table_prefix' => $s_db_prefix
		);
	}



	/**
	 * Построеоние списка полей, по еоторым будет производится поиск
	 * @return string
	 */
	public function buildSubQueryFields(){
		$a_sub_query_fiels = array();

		foreach($this->search_fields as $_item_field){
			$a_sub_query_fiels[] = "REPLACE({$_item_field},  ' ',  '' ) AS {$_item_field}";
		}

		return implode(', ', $a_sub_query_fiels);
	}



	/**
	 * Построение подзапроса для нормализарованной строки
	 * @return string
	 */
	public function buildNormalizeSubQuery($search_query, $s_alias = "normalize"){
		$a_query = explode(' ', $search_query);
		$a_normalize_sub_query = array();

		foreach($this->search_fields as $_item_field){
			$_a_norm_item = array();
			foreach ($a_query as $s_item) {
				$_a_norm_item[] = "{$s_alias}.$_item_field LIKE '%{$s_item}%'";
			}

			$a_normalize_sub_query[] = '('. implode(' AND ', $_a_norm_item) . ')';
		}

		return  implode(' OR ', $a_normalize_sub_query);
	}



	/**
	 * Построение результирующего запроса
	 * @return string
	 */
	public function buildQuery($search_query){

		$s_sub_query_fiels = $this->buildSubQueryFields();
		$s_normalize_sub_query = $this->buildNormalizeSubQuery($search_query);

		$s_sql = "
			SELECT sc.* FROM {$this->getFullTableName('site_content')} AS sc,

			(SELECT id, {$s_sub_query_fiels} FROM  {$this->getFullTableName('site_content')} WHERE 1)normalize

			WHERE sc.template=13
			 AND sc.id=normalize.id
			 AND {$s_normalize_sub_query}
			 AND sc.published=1 AND sc.deleted=0
		";

		return $this->sql = $s_sql;
	}



	/**
	 * @param $s_doc_condition
	 * @param $field_list
	 * @param bool $tv_list
	 * @param string $order_by
	 * @param string $order_type
	 * @param string $limit
	 * @param bool $filters
	 * @return string
	 * Строит запрос который выбирает все документы соответствующие условию $s_doc_condition
	 * Присоединяет к ним запрошенный список TV:
	 * | id | pagetitle |...document_fields | images(имя TV) | price(имя TV)
	 */
	public function buildDocumentQuery($s_doc_condition, $field_list, $tv_list = false, $order_by = 'sc.menuindex', $order_type = 'ASC', $limit = '', $filters = false) {

		$a_res = array();

		//Проверяем данные
		if (is_string($field_list)) { //необходимо получить масив
			$field_list = str_replace(" ", "", $field_list);
			$field_list = explode(",", $field_list);
		}

		if ($tv_list) {
			//Необходимо получить строку
			if (is_string($tv_list)) {
				$a_tv_list = ModExt::explodeTrim(",", $tv_list);
			}else{
				$a_tv_list = (array) $tv_list;
			}

			//Build sql query
			$a_sql = array();

			$a_sql['select'] = array("SELECT sc.*"); //, IF(tvc.value !='', tvc.value, tv.default_text) as tv_value, tvtpl.`tmplvarid`, tv.name AS tmplvarname \n";
			$a_sql['from'] = array("FROM {$this->getFullTableName('site_content')} as sc");
			$a_sql['join'] = array();
			$a_sql['where'] = array("WHERE {$s_doc_condition}");
			$a_sql['order'] = "ORDER BY CAST({$order_by} AS SIGNED) {$order_type}";

			if ($limit) $a_sql['limit'] = "LIMIT {$limit}";

			if($a_tv_list){
				foreach ($a_tv_list as $_item) {
					if (intval($_item)) {
						$s_name = "tv_id_{$_item}";
					} else {
						$s_tv_id_query = "(SELECT tv.id FROM `modx_site_tmplvars` AS tv WHERE tv.name='{$_item}')";
						$s_name = $_item;
					}

					$a_sql['select'][] = ", IF(tvc_{$_item}.value  !='', tvc_{$_item}.value,  tv_{$_item}.default_text)  AS {$s_name}";
					$a_sql['join'][] =
							"LEFT JOIN {$this->getFullTableName('site_tmplvar_contentvalues')} as tvc_{$_item}
						ON (tvc_{$_item}.`contentid` = sc.`id`". (isset($s_tv_id_query) ? " AND tvc_{$_item}.`tmplvarid` = {$s_tv_id_query})" : "");
					$a_sql['join'][] =
							"LEFT JOIN {$this->getFullTableName('site_tmplvars')} as tv_{$_item} ON (tv_{$_item}."
									. (intval($_item) ? "id" : "name") . "= '{$_item}')";
				}
			}

			if ($filters) {

				$a_sql['join'][] = "
				LEFT JOIN {$this->getFullTableName('site_tmplvar_contentvalues')} as filters
					ON (sc.`id` = filters.`contentid`)
				";

				$a_sql['join'][] = "
				LEFT JOIN {$this->getFullTableName('site_tmplvars')} as filters_name
					ON (filters_name.`id` = filters.`tmplvarid`)
				";

				$s_filters = '\'' . ModExt::implode_recursive("', '", $filters) . '\'';
				$s_filters_name = '\'' . ModExt::implode_recursive("', '", array_keys($filters)) . '\'';

				$a_sql['where'][] = "AND filters_name.`name` IN ({$s_filters_name}) AND filters.`value` IN ($s_filters)";
			}

			$s_sql = ModExt::implode_recursive("\n", $a_sql);
		} else {

			//Составляем SQL
			$s_sql = "
					SELECT sc.*
					FROM {$this->getFullTableName('site_content')} AS sc
					WHERE {$s_doc_condition}
					ORDER BY {$order_by} {$order_type}
			";
		}

		return $s_sql;
	}
	
	
	    # returns the full table name based on db settings
    function getFullTableName($tbl) {
        return $this->config['dbase'] . ".`" . $this->config['table_prefix'] . $tbl . "`";
    }
}
