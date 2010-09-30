Fat-Free Framework Plugins
=================

**AxonREST** 

A plugin that handles PUT/POST/GET/DELETE operations against a named Axon model. You use it like so:

    F3::map('/rest/@name',     'AxonREST');
    F3::map('/rest/@name/@id', 'AxonREST');

AxonREST will pick up @name as the model name. You can also use it like this:

    AxonREST::setObjectName('my_email_list');
    F3::map('/rest/emails',         'AxonREST');
    F3::map('/rest/emails/@id', 'AxonREST');

And there is a hook for fine-grain access checks:

    AxonRest::setAccessDelegate(function($object_name, $action, $object_id=null){
    	if ($object_name == 'emails') {
    		if ($object_id !== 2) { 
    			return true;
    		}
    	}
    	return false;
    });

PUT, POST, and DELETE operations work as one would expect. GET with an ID serializes the Axon model and spits it out. 

GET without an ID accepts filtering parameters in the query string: group_by, order_by, limit, offset, field_name=blah, field_name=<10, field_name=>10, field_name=<>10, field_name=%blah%. Records returned can be filtered by the access check delegate.

I must mention two things about this plugin. First, I've hardcoded the PK name to "id", as it looks like I can't grab that from the Axon model by key type. Still looking into this one. Second, I'm also accessing Axon using some scary PHP eval() voodoo. Other than that, it works pretty nicely.

Data is all JSON encoded for output.

**Currency**

A simple plugin that converts a money value from one currency to another. It pulls from Google Finance, so I would not recommend pushing on it too hard.

    echo Currency::convertAmount(1.50, 'EUR', 'USD');
    // 2.0347


**TableBuilder**

An HTML table builder with basic support for paging. Generates W3C-validating table HTML code.

    $rows = get_db_rows_function();
    $table = TableBuilder::fromRows($rows);
    $table->border = 1;
    $table->cellpadding = 5;
    $table->width = '100%';
    
    $pager = new TablePager($offset=0, $limit=10, $max=300);
    $pager->setLink('/test/index.php?page=');
    
    $table->addPager($pager);
    
    if ($table->column('id')) {
    	$table->column('id')->setWidth(40)
    					    ->setLabel("ID")
    					    ->setAlign("right")
    					    ->linkify('/person/');
    }

Or simply:

    echo new TableBuilder($rows);



**System Profile**

A plugin for grabbing system information like online users and load levels. Also supports basic interpretation of load levels, which allows for adaptive throttling.

    if (!SystemProfile::systemIsOkay()) {
    	$load = SystemProfile::getLoadLevels();
    	F3::set('THROTTLE', $load['5m_avg'] * 300);
    }

It's only been tested on Ubuntu and FreeBSD. It doesn't work on Windows. 

  
