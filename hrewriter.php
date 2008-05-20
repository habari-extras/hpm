<?php

class HRewriter
{
	private $rules = array();
	private static $instance;
	
	
	static public function instance()
	{
		if ( ! self::$instance ) {
			self::$instance = new HRewriter;
		}
		return self::$instance;
	}
	
	public function __construct()
	{
		Plugins::register( array('HRewriter', 'filter_rewrite_rules'), 'filter', 'rewrite_rules', 1 );
	}
	
	static public function add_rule( $name, $regex, $build_str, $handler, $action, $priority = 1 )
	{
		self::instance()->rules[$name]= array( $regex, $build_str, $handler, $action, $priority );
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
			$rule->description= 'Another custom rewrite rule by HRewriter';
			$rules[] = $rule;
		}
		return $rules;
	}
}

?>