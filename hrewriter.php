<?php

class CRewriter
{
	private $rules = array();
	private static $instance;
	
	
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new CRewriter;
		}
		return self::$instance;
	}
	
	public function __construct()
	{
		Plugins::register( array('CRewriter', 'filter_rewrite_rules'), 'filter', 'rewrite_rules', 1 );
	}
	
	static public function add_rule( $name, $regex, $build_str, $handler, $action, $priority = 1 )
	{
		self::instance()->rules[$name] = array( $regex, $build_str, $handler, $action, $priority );
	}
	
	static public function add_url_rule( $build_str, $handler, $action )
	{
		$arr = split( '/', $build_str );
		
		$re_arr = preg_replace('/^([^"\']+)$/', "(.+)", $arr);
		$re_arr = preg_replace('/^["\'](.+)["\']$/', '\\1', $re_arr);
		
		$str_arr = preg_replace('/^([^"\']+)$/', '{$\\1}', $arr);
		$str_arr = preg_replace('/^["\'](.+)["\']$/', '\\1', $str_arr);
		
		$regex = '/^' . implode( '\\/', $re_arr ) . '\\/?$/i';
		$build_str = implode( '/', $str_arr );
		
		self::add_rule( $action, $regex, $build_str, $handler, $action, 1 );
	}
	
	static public function filter_rewrite_rules( $rules ) {
		foreach ( self::instance()->rules as $name => $c_rule )
		{
			$rule = new RewriteRule();
			$rule->name = $name;
			$rule->parse_regex = $c_rule[0];
			$rule->build_str = $c_rule[1];
			$rule->handler = $c_rule[2];
			$rule->action = $c_rule[3];
			$rule->priority = $c_rule[4];
			$rule->is_active = 1;
			$rule->is_system = 0;
			$rule->description= 'Another custom rewrite rule by CRewriter';
			$rules[] = $rule;
		}
		return $rules;
	}
}

?>