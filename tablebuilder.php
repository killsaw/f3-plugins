<?php

/**
	Table Builder plugin for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2010-2011 Killsaw
	Steven Bredenberg <steven@killsaw.com>

		@package TableBuilder
		@version 1.0.0
**/

//! Plugin for building HTML tables.
class TableBuilder extends Core {

	//! Minimum framework version required to run
	const F3_Minimum='1.4.0';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_NoRecords='No records.',
		TEXT_TableNoProperty='Table does not have property {@CONTEXT}';
	//@}

	//@{
	//! TableBuilder properties
	protected $header=array();
	protected $rows=array();
	protected $pager=NULL;
	protected $properties=array();
	//@}

	/**
		Return an instance of TableBuilder with $rows set.
			@return TableBuilder
			@param $rows array
			@public
	**/	
	public static function fromRows(array $rows) {
		$table=new TableBuilder;
		$table->addRows($rows);
		return $table;
	}
	
	/**
		Set table column headers given an array of names.
			@param $fields array
			@public
	**/	
	public function setHeader(array $fields) {
		$this->header = array();
		foreach($fields as $f) {
			$this->header[$f] = new TableColumn($f);
		}
	}
	
	/**
		Add a row to the table.
			@param $row array
			@public
	**/	
	public function addRow(array $row) {
		$this->rows[] = $row;
	}

	/**
		Convenience method for bulk adding rows.
			@param $rows array
			@public
	**/	
	public function addRows(array $rows) {
		if (count($rows) > 0) {
			$this->setHeader(array_keys($rows[0]));
			$this->rows += $rows;
		}
	}
	
	/**
		Retrieve a TableColumn instance by name.
			@return TableColumn|null
			@param $name string
			@public
	**/
	public function column($name) {
		if (array_key_exists($name, $this->header)) {
			return $this->header[$name];
		}
		return null;
	}

	/**
		Add a table pager for pagination display.
			@param $pager TablePager
			@public
	**/
	public function addPager(TablePager $pager) {
		$this->pager = $pager;
	}
	
	/**
		Generate complete HTML table.
			@return string
			@public
	**/
	public function toString() {
		
		// Build table attribute string.
		$attr = '';
		foreach($this->properties as $k=>$v) {
			$attr .= sprintf(' %s="%s"', $k, $v);
		}
		
		$col_count = count($this->header);
		foreach($this->header as $h) {
			if ($h->display === false) {
				$col_count--;
			}
		}
		
		$html = sprintf("<table%s>", $attr);
		
		// Check for empty recordset.
		$has_records = ($this->rows && $col_count);
		
		if ($has_records) {
			// Build header.
			$html .= '<thead><tr>'.join('', $this->header).'</tr></thead>';
	
			// Optionally build pager.
			if ($this->pager instanceof TablePager) {
				$html .= sprintf('<tfoot><tr><td align="center" colspan="%d">%s</td></tr></tfoot>', 
								$col_count, $this->pager);
			}
		}
		
		// Build body.
		$html .= '<tbody>';
		
		if (!$has_records) {
			// Handle empty recordset.
			$html .= sprintf('<tr><td>%s</td></tr>', self::TEXT_NoRecords);
		} else {
			foreach($this->rows as $row) {
				$html .= '<tr>';
				foreach($row as $name=>$field) {
					
					// Support column hiding.
					if ($this->header[$name]->display === false) {
						continue;
					}
					
					// Support automatic field linking.
					if ($format = $this->header[$name]->getLinkFormat()) {
						$field = sprintf('<a href="%s%s">%s</a>', $format, $field, $field);
					}
					
					// Bum attributes from column. Not ideal, but good enough.
					$props = $this->header[$name]->getProperties($toString=true);
					
					$html .= sprintf('<td%s>%s</td>', $props, $field);
				}
				$html .= '</tr>';
			}
		}
		$html .= '</tbody></table>';
		
		return $html;
	}

	/**
		Set a table HTML attribute
			@param $name string
			@param $value mixed
			@public
	**/
	public function __set($name, $value) {
		$this->properties[$name] = $value;
	}
	
	/**
		Get a table HTML attribute
			@return mixed
			@param $name string
			@public
	**/
	public function __get($name) {
		if (array_key_exists($name, $this->properties)) {
			return $this->properties[$name];
		}
		self::$global['CONTEXT'] = $name;
		trigger_error(self::TEXT_TableNoProperty);
	}
	
	/**
		Automagic. Return generated HTML table.
			@return string
			@public
	**/
	public function __toString() {
		return $this->toString();
	}
	
	/**
		TableBuilder constructor
			@public
	**/
	public function __construct($rows=array()) {
		$this->addRows($rows);
	}
}

//! TableBuilder column.
class TableColumn {

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_ColumnNoProperty='Column does not have property {@CONTEXT}.',
		TEXT_NoMethod='Method {@CONTEXT} does not exist.';
	//@}

	//@{
	//! TableColumn properties
	protected $label=NULL;
	protected $link_format=NULL;
	protected $properties=array();
	protected $display=true;
	//@}
	
	/**
		Set displayed column text.
			@return TableBuilder
			@param $label string
			@public
	**/
	public function setLabel($label) {
		$this->label=$label;
		return $this;
	}
	
	/**
		Get displayed column text.
			@return string
			@public
	**/
	public function getLabel() {
		return $this->label;
	}

	/**
		Sets link format for column row cells. Prepends cell
		value with format.
			@param $format string
			@public
	**/
	public function linkify($format) {
		$this->link_format = $format;
	}
	
	/**
		Get link format for column row cells.
			@return string
			@public
	**/
	public function getLinkFormat() {
		return $this->link_format;
	}

	/**
		Get HTML attributes for column.
			@param $toString bool
			@return string
			@public
	**/
	public function getProperties($toString=false) {
		if ($toString) {
			$attr = '';
			foreach($this->properties as $k=>$v) {
				$attr .= " {$k}=\"{$v}\"";
			}
			return $attr;			
		} else {
			return $this->properties;
		}
	}
		
	/**
		Generate HTML for column header.
			@return string
			@public
	**/
	public function toString() {
		if ($this->display !== false) {
			return sprintf("<th%s>%s</th>", 
					$this->getProperties($toString=true), 
					$this->label);
		} else {
			return '';
		}
	}
	
	/**
		Set column HTML attribute.
			@param $name string
			@param $value mixed
			@public
	**/
	public function __set($name, $value) {
		$this->properties[$name] = $value;
	}
	
	/**
		Get column HTML attribute.
			@return mixed
			@param $name string
			@public
	**/
	public function __get($name) {
		if (array_key_exists($name, $this->properties)) {
			return $this->properties[$name];
		}
	}

	/**
		Pickup on set() method calls for set() chaining.
			@return TableColumn
			@param $name string
			@param $args array
			@public
	**/
	public function __call($name, $args) {
		if (preg_match('/^set(.+)/', $name, $match)) {
			$this->properties[strtolower($match[1])] = $args[0];
			return $this;
		}
		self::$global['CONTEXT'] = $name;
		trigger_error(self::TEXT_NoMethod);
	}
	
	/**
		Automagic. Returns column header HTML.
			@return string
	**/	
	public function __toString() {
		return $this->toString();
	}
		
	/**
		TableColumn constructor
			@param $label string
			@public
	**/
	public function __construct($label) {
		$this->label = $label;
	}
}

//! TableBuilder pager.
class TablePager {

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_ColumnNoProperty='Column does not have property {@CONTEXT}.';
	//@}

	//@{
	//! TableColumn properties
	protected $link=NULL;
	protected $limit=0;
	protected $offset=0;
	protected $max=0;
	//@}
				
	/**
		Sets a prefix for pager links.
			@param $link string
			@public
	**/
	public function setLink($link) {
		$this->link = $link;
	}
	
	/**
		Generate HTML for pager.
			@return string
			@public
	**/
	public function toString() {
		$page_count = ceil($this->max / $this->limit);
		
		if ($this->offset > 0) {
			$curr_page = ceil($this->offset / $this->limit);
		} else {
			$curr_page = 1;
		}
		
		$html = '';
		for($i=1; $i <= $page_count; $i++) {
			$html .= sprintf('<a href="%s%d"%s>%d</a> ', $this->link, $i, 
							($i == $curr_page)?' class="active_page"':'', $i);
		}
		return $html;
	}

	/**
		Automagic. Return pager HTML.
			@return string
	**/	
	public function __toString() {
		return $this->toString();
	}

	/**
		TablePager constructor
			@param $offset integer
			@param $limit integer
			@param $max integer
			@public
	**/
	public function __construct($offset, $limit, $max=-1) {
		if ($max < 0) {
			$max = $limit;
		}
		
		$this->limit = $limit;
		$this->offset = $offset;
		$this->max = $max;
	}
}
