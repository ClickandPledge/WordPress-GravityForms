<?php

/**
* class for admin screens
*/
class GFCnpAdmin {

	public $settingsURL;

	private $plugin;

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		// handle change in settings pages
		if (class_exists('GFCommon')) {
			if (version_compare(GFCommon::$version, '1.6.99999', '<')) {
				// pre-v1.7 settings
				$this->settingsURL = admin_url('admin.php?page=gf_settings&addon=Click+%26+Pledge');
			}
			else {
				// post-v1.7 settings
				$this->settingsURL = admin_url('admin.php?page=gf_settings&subview=Click+%26+Pledge');
			}
		}

		// handle admin init action
		add_action('admin_init', array($this, 'adminInit'));

		// add GravityForms hooks
		add_filter('gform_currency_setting_message', array($this, 'gformCurrencySettingMessage'));
		add_action('gform_payment_status', array($this, 'gformPaymentStatus'), 10, 3);
		add_action('gform_after_update_entry', array($this, 'gformAfterUpdateEntry'), 10, 2);
		add_action("gform_entry_info", array($this, 'gformEntryInfo'), 10, 2);

		// hook for showing admin messages
		add_action('admin_notices', array($this, 'actionAdminNotices'));

		// add action hook for adding plugin action links
		add_action('plugin_action_links_' . GFCNP_PLUGIN_NAME, array($this, 'addPluginActionLinks'));

		// hook for adding links to plugin info
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);
		
		//creates the subnav left menu
        add_filter("gform_addon_navigation", array('GFCnpAdmin', 'create_menu'));

		GFCnpData:: update_table();

		if(self::is_cnp_page()){
                //enqueueing sack for AJAX requests
                wp_enqueue_script(array("sack"));
                //loading data lib
                require_once(self::get_base_path() . "/class.GFCnpData.php");
          
                //loading Gravity Forms tooltips
                require_once(GFCommon::get_base_path() . "/tooltips.php");
                add_filter('gform_tooltips', array('GFCnpAdmin', 'tooltips'));
            }
		else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){
                //loading data class
                require_once(self::get_base_path() . "/class.GFCnpData.php");

                add_action('wp_ajax_gf_select_cnp_form', array('GFCnpAdmin', 'select_cnp_form'));



            }
	}
	
	//Returns the physical path of the plugin's root folder
    private static function get_base_path(){
        $folder = basename(dirname(__FILE__));
        return WP_PLUGIN_DIR . "/" . $folder;
    }
	
	 public static function select_cnp_form(){
		check_ajax_referer("gf_select_cnp_form", "gf_select_cnp_form");
		$type = $_POST["type"];

        $form_id =  intval($_POST["form_id"]);

        $setting_id =  intval($_POST["setting_id"]);
		$form = RGFormsModel::get_form_meta($form_id);
		
		die("EndSelectForm(" . GFCommon::json_encode($form) . ");");
	 }
	 
	 private static function get_form_fields($form){

        $fields = array();



        if(is_array($form["fields"])){

            foreach($form["fields"] as $field){

                if(is_array(rgar($field,"inputs"))){



                    foreach($field["inputs"] as $input)

                        $fields[] =  array($input["id"], GFCommon::get_label($field, $input["id"]));

                }

                else if(!rgar($field, 'displayOnly')){

                    $fields[] =  array($field["id"], GFCommon::get_label($field));

                }

            }

        }

        return $fields;

    }
	
	private static function is_cnp_page(){

        $current_page = trim(strtolower(RGForms::get("page")));

        return in_array($current_page, array("gfcnp"));

    }
	
	/**
	* test whether GravityForms plugin is installed and active
	* @return boolean
	*/
	public static function isGfActive() {
		return class_exists('RGForms');
	}

	/**
	* handle admin init action
	*/
	public function adminInit() {
		if (isset($_GET['page'])) {
			switch ($_GET['page']) {
				case 'gf_settings':
					// add our settings page to the Gravity Forms settings menu
					RGForms::add_settings_page('Click & Pledge', array($this, 'optionsAdmin'));
					break;
			}
		}
	}


	/**
	* show admin messages
	*/
	public function actionAdminNotices() {
		if (!self::isGfActive()) {
			$this->plugin->showError('Gravity Forms Click & Pledge requires <a href="http://www.gravityforms.com/">Gravity Forms</a> to be installed and activated.');
		}
	}

	/**
	* action hook for adding plugin action links
	*/
	public function addPluginActionLinks($links) {
		// add settings link, but only if GravityForms plugin is active
		if (self::isGfActive()) {
			$settings_link = sprintf('<a href="%s">%s</a>', $this->settingsURL, __('Settings'));
			array_unshift($links, $settings_link);
		}

		return $links;
	}

	/**
	* action hook for adding plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		if ($file == GFCNP_PLUGIN_NAME) {
			$links[] = '<a href="http://wordpress.org/support/plugin/gravityforms-cnp">' . __('Get help') . '</a>';
		}

		return $links;
	}

	/**
	* action hook for showing currency setting message
	* @param array $menus
	* @return array
	*/
	public function gformCurrencySettingMessage() {
		echo "<div class='gform_currency_message'>NB: Gravity Forms Click & Pledge only supports 'USD', 'EUR', 'CAD', 'GBP'.</div>\n";
	}

	/**
	* action hook for building the entry details view
	* @param int $form_id
	* @param array $lead
	*/
	public function gformEntryInfo($form_id, $lead) {
		$payment_gateway = gform_get_meta($lead['id'], 'payment_gateway');
		if ($payment_gateway == 'gfcnp') {
			$authcode = gform_get_meta($lead['id'], 'authcode');
			if ($authcode) {
				echo 'Auth Code: ', esc_html($authcode), "<br /><br />\n";
			}
		}
	}

	/**
	* action hook for processing admin menu item
	*/
	public function optionsAdmin() {
		$admin = new GFCnpOptionsAdmin($this->plugin, 'gfcnp-options', $this->settingsURL);
		$admin->process();
	}

	/**
	* allow edits to payment status
	* @param string $payment_status
	* @param array $form
	* @param array $lead
	* @return string
	*/
    public function gformPaymentStatus($payment_status, $form, $lead) {
		// make sure payment is not Approved, and that we're editing the lead
		if ($payment_status == 'Approved' || strtolower(rgpost('save')) <> 'edit') {
			return $payment_status;
		}

		// make sure payment is one of ours (probably)
		$payment_gateway = gform_get_meta($lead['id'], 'payment_gateway');
		if ((empty($payment_gateway) && $this->plugin->hasFieldType($form['fields'], 'creditcard')) || $payment_gateway != 'gfcnp') {
			return $payment_status;
		}

		// make sure payment isn't a recurring payment
		if ($this->plugin->hasFieldType($form['fields'], GFCNP_FIELD_RECURRING)) {
			return $payment_status;
		}

		// create drop down for payment status
		//~ $payment_string = gform_tooltip("paypal_edit_payment_status","",true);
		$input = <<<HTML
<select name="payment_status">
 <option value="$payment_status" selected="selected">$payment_status</option>
 <option value="Approved">Approved</option>
</select>

HTML;

		return $input;
    }

	/**
	* update payment status if it has changed
	* @param array $form
	* @param int $lead_id
	*/
	public function gformAfterUpdateEntry($form, $lead_id) {
		// make sure we have permission
		check_admin_referer('gforms_save_entry', 'gforms_save_entry');

		// check that save action is for update
		if (strtolower(rgpost("save")) <> 'update')
			return;

		// make sure payment is one of ours (probably)
		$payment_gateway = gform_get_meta($lead_id, 'payment_gateway');
		if ((empty($payment_gateway) && $this->plugin->hasFieldType($form['fields'], 'creditcard')) || $payment_gateway != 'gfcnp') {
			return;
		}

		// make sure we have a new payment status
		$payment_status = rgpost('payment_status');
		if (empty($payment_status)) {
			return;
		}

		// update payment status
		$lead = GFFormsModel::get_lead($lead_id);
		$lead["payment_status"] = $payment_status;

		GFFormsModel::update_lead($lead);
	}
	
	public static function create_menu($menus){
        // Adding submenu if user has access
        	$permission = self::has_access("gravityforms_cnp");
        if(!empty($permission)) {
            $menus[] = array("name" => "gfcnp", "label" => __("Click & Pledge", "gfcnp"), "callback" =>  array("GFCnpAdmin", "cnp_page"), "permission" => $permission);
		}
        return $menus;
    }
	
	public static function cnp_page(){

        $view = rgget("view");

        if($view == "edit")

            self::edit_page(rgget("id"));

        else if($view == "stats")

            self::stats_page(rgget("id"));

        else

            self::list_page();

    }
	
	 // Edit Page

    private static function edit_page(){
	 ?>
	 <div class="wrap">
           <img alt="<?php _e("Click & Pledge Transactions", "gfcnp") ?>" height="50" src="<?php echo self::get_base_url()?>/images/cnp logo.jpg" style="float:left; margin:0px 7px 0 0;"/>
		  <h2><?php _e("Click & Pledge Settings", "gravityformsauthorizenet") ?></h2>
		  
		  <?php
        //getting setting id (0 when creating a new one)
        $id = !empty($_POST["cnp_setting_id"]) ? $_POST["cnp_setting_id"] : absint($_GET["id"]);

        $config = empty($id) ? array("meta" => array(), "is_active" => true) : GFCnpData::get_feed($id);
		
		if(rgpost("gf_cnp_submit")){
			$config["form_id"] = absint(rgpost("gf_cnp_form"));
			$config["meta"]["enable_receipt"] = rgpost('gf_cnp_enable_receipt');
			
			$config = apply_filters('gform_cnp_save_config', $config);
			$id = GFCnpData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
			?>
            <div class="updated fade" style="padding:6px"><?php echo sprintf(__("Feed Updated. %sback to list%s", "gfcnp"), "<a href='?page=gfcnp'>", "</a>") ?></div>

             <?php
		}

        $setup_fee_field_conflict = false; //initialize variable
		$form = isset($config["form_id"]) && $config["form_id"] ? $form = RGFormsModel::get_form_meta($config["form_id"]) : array();

        $settings = get_option("gf_cnp_settings");
		
		
		?>
		<form method="post" action="">
            <input type="hidden" name="cnp_setting_id" value="<?php echo $id ?>" />
			<div id="cnp_form_container" valign="top" class="margin_vertical_10">

                <label for="gf_cnp_form" class="left_header"><?php _e("Gravity Form", "gfcnp"); ?></label>



                <select id="gf_cnp_form" name="gf_cnp_form" onchange="SelectForm(jQuery('#gf_cnp_type').val(), jQuery(this).val(), '<?php echo rgar($config, 'id') ?>');">

                    <option value=""><?php _e("Select a form", "gfcnp"); ?> </option>

                    <?php



                    $active_form = rgar($config, 'form_id');

                    $available_forms = GFCnpData::get_available_forms($active_form);



                    foreach($available_forms as $current_form) {

                        $selected = absint($current_form->id) == rgar($config, 'form_id') ? 'selected="selected"' : '';

                        ?>



                            <option value="<?php echo absint($current_form->id) ?>" <?php echo $selected; ?>><?php echo esc_html($current_form->title) ?> (Form Id: <?php echo absint($current_form->id) ?>)</option>



                        <?php

                    }

                    ?>

                </select>

                &nbsp;&nbsp;

                <img src="<?php echo GFCnpAdmin::get_base_url() ?>/images/loading.gif" id="cnp_wait" style="display: none;"/>



                <div id="gf_cnp_invalid_product_form" class="gf_cnp_invalid_form"  style="display:none;">

                    <?php _e("The form selected does not have any Product fields. Please add a Product field to the form and try again.", "gfcnp") ?>

                </div>

                <div id="gf_cnp_invalid_creditcard_form" class="gf_cnp_invalid_form" style="display:none;">

                    <?php _e("The form selected does not have a credit card field. Please add a credit card field to the form and try again.", "gfcnp") ?>

                </div>
				
				<div id="cnp_field_group" valign="top" <?php echo empty($config["form_id"]) ? "style='display:none;'" : "" ?>>
				<!--<div class="margin_vertical_10">
                    <label class="left_header"><?php _e("Options", "gfcnp"); ?></label>
					<ul style="overflow:hidden;">
                        <li id="cnp_enable_receipt">
                            <input type="checkbox" name="gf_cnp_enable_receipt" id="gf_cnp_enable_receipt" value="1" <?php echo rgar($config["meta"], 'enable_receipt') ? "checked='checked'"  : "" ?> />

                            <label class="inline" for="gf_cnp_enable_receipt"><?php _e("Send Click & Pledge email receipt.", "gfcnp"); ?></label>

                        </li>
					</ul>
				</div>-->
				
				<div id="cnp_submit_container" class="margin_vertical_30">

                    <input type="submit" name="gf_cnp_submit" value="<?php echo empty($id) ? __("  Save  ", "gfcnp") : __("Update", "gfcnp"); ?>" class="button-primary"/>

                    <input type="button" value="<?php _e("Cancel", "gfcnp"); ?>" class="button" onclick="javascript:document.location='admin.php?page=gfcnp'" />

                </div>
				
				</div>

            </div>
		</form>
	</div>
	
	<script>
	
	
			
	function EndSelectForm(form_meta){

                //setting global form object
                form = form_meta;

                var type = jQuery("#gf_cnp_type").val();

                jQuery(".gf_cnp_invalid_form").hide();

                if( (type == "product" || type =="subscription") && GetFieldsByType(["product"]).length == 0){

                    jQuery("#gf_cnp_invalid_product_form").show();

                    jQuery("#cnp_wait").hide();

                    return;

                }

                else if( (type == "product" || type =="subscription") && GetFieldsByType(["creditcard"]).length == 0){

                    jQuery("#gf_cnp_invalid_creditcard_form").show();

                    jQuery("#cnp_wait").hide();

                    return;

                }



                jQuery(".cnp_field_container").hide();

                var post_fields = GetFieldsByType(["post_title", "post_content", "post_excerpt", "post_category", "post_custom_field", "post_image", "post_tag"]);

                if(type == "subscription" && post_fields.length > 0){

                    jQuery("#cnp_post_update_action").show();

                }


                jQuery("#cnp_field_container_" + type).show();

                jQuery("#cnp_field_group").slideDown();

                jQuery("#cnp_wait").hide();

            }
			
	function GetFieldsByType(types){

		var fields = new Array();

		for(var i=0; i<form["fields"].length; i++){

			if(IndexOf(types, form["fields"][i]["type"]) >= 0)

				fields.push(form["fields"][i]);

		}

		return fields;

	}



	function IndexOf(ary, item){

		for(var i=0; i<ary.length; i++)

			if(ary[i] == item)

				return i;



		return -1;

	}
	
	function SelectForm(type, formId, settingId){

                if(!formId){

                    jQuery("#cnp_field_group").slideUp();

                    return;

                }



                jQuery("#cnp_wait").show();

                jQuery("#cnp_field_group").slideUp();



                var mysack = new sack(ajaxurl);

                mysack.execute = 1;

                mysack.method = 'POST';

                mysack.setVar( "action", "gf_select_cnp_form" );

                mysack.setVar( "gf_select_cnp_form", "<?php echo wp_create_nonce("gf_select_cnp_form") ?>" );

                mysack.setVar( "type", type);

                mysack.setVar( "form_id", formId);

                mysack.setVar( "setting_id", settingId);

                mysack.encVar( "cookie", document.cookie, false );

                mysack.onError = function() {jQuery("#cnp_wait").hide(); alert('<?php _e("Ajax error while selecting a form", "gfcnp") ?>' )};
                mysack.runAJAX();
                return true;

            }
	</script>
	 <?php
	}
	//Returns the url of the plugin's root folder
    private static function get_base_url(){
        return plugins_url(null, __FILE__);
    }
	private static function list_page(){
		
		if(rgpost('action') == "delete"){
            check_admin_referer("list_action", "gf_cnp_list");
            $id = absint($_POST["action_argument"]);
            GFCnpData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feed deleted.", "gfcnp") ?></div>
            <?php
        }
		else if (!empty($_POST["bulk_action"])){
            check_admin_referer("list_action", "gf_cnp_list");
            $selected_feeds = $_POST["feed"];
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFCnpData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feeds deleted.", "gfcnp") ?></div>
            <?php
        }
		?>
		<div class="wrap">
            <img alt="<?php _e("Click & Pledge Transactions", "gfcnp") ?>" height="50" src="<?php echo self::get_base_url()?>/images/cnp logo.jpg" style="float:left; margin:0px 7px 0 0;"/>
			<h2><?php  _e("Click & Pledge Forms", "gfcnp"); ?>
                <a class="button add-new-h2" href="admin.php?page=gfcnp&view=edit&id=0"><?php _e("Add New", "gfcnp") ?></a>
            </h2>
			
			<form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_cnp_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>
				
				<div class="tablenav">

                    <div class="alignleft actions" style="padding:8px 0 7px 0;">

                        <label class="hidden" for="bulk_action"><?php _e("Bulk action", "gfcnp") ?></label>

                        <select name="bulk_action" id="bulk_action">

                            <option value=''> <?php _e("Bulk action", "gfcnp") ?> </option>

                            <option value='delete'><?php _e("Delete", "gfcnp") ?></option>

                        </select>

                        <?php

                        echo '<input type="submit" class="button" value="' . __("Apply", "gfcnp") . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __("Delete selected feeds? ", "gfcnp") . __("\'Cancel\' to stop, \'OK\' to delete.", "gfcnp") .'\')) { return false; } return true;"/>';

                        ?>

                    </div>

                </div>
				
				<table class="widefat fixed" cellspacing="0">

                    <thead>

                        <tr>

                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>

                            <th scope="col" id="active" class="manage-column check-column"></th>

                            <th scope="col" class="manage-column"><?php _e("Form", "gfcnp") ?></th>

                            

                        </tr>

                    </thead>



                    <tfoot>

                        <tr>

                            <th scope="col" id="cb" class="manage-column column-cb check-column" style="" ><input type="checkbox" /></th>

                            <th scope="col" id="active" class="manage-column check-column"></th>

                            <th scope="col" class="manage-column"><?php _e("Form", "gfcnp") ?></th>

                          

                        </tr>

                    </tfoot>



                    <tbody class="list:user user-list">

                        <?php





                        $settings = GFCnpData::get_feeds();

                        if(is_array($settings) && sizeof($settings) > 0){

                            foreach($settings as $setting){

                                ?>

                                <tr class='author-self status-inherit' valign="top">

                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $setting["id"] ?>"/></th>

                                    <td width="40%"><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($setting["is_active"]) ?>.png" alt="<?php echo $setting["is_active"] ? __("Active", "gfcnp") : __("Inactive", "gfcnp");?>" title="<?php echo $setting["is_active"] ? __("Active", "gfcnp") : __("Inactive", "gfcnp");?>" onclick="ToggleActive(this, <?php echo $setting['id'] ?>); " /></td>

                                    <td class="column-title">

                                        <a href="admin.php?page=gfcnp&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gfcnp") ?>"><?php echo $setting["form_title"] ?></a>

                                        <div class="row-actions">

                                            <span class="edit">

                                            <a title="<?php _e("Edit", "gfcnp")?>" href="admin.php?page=gfcnp&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gfcnp") ?>"><?php _e("Edit", "gfcnp") ?></a>

                                            |

                                            </span>

                                            <!--<span>

                                            <a title="<?php _e("View Stats", "gfcnp")?>" href="admin.php?page=gfcnp&view=stats&id=<?php echo $setting["id"] ?>" title="<?php _e("View Stats", "gfcnp") ?>"><?php _e("Stats", "gfcnp") ?></a>

                                            |

                                            </span>-->

                                            <span>

                                            <a title="<?php _e("View Entries", "gfcnp")?>" href="admin.php?page=gf_entries&view=entries&id=<?php echo $setting["form_id"] ?>" title="<?php _e("View Entries", "gfcnp") ?>"><?php _e("Entries", "gfcnp") ?></a>

                                            |

                                            </span>

                                            <span>

                                            <a title="<?php _e("Delete", "gfcnp") ?>" href="javascript: if(confirm('<?php _e("Delete this feed? ", "gfcnp") ?> <?php _e("\'Cancel\' to stop, \'OK\' to delete.", "gfcnp") ?>')){ DeleteSetting(<?php echo $setting["id"] ?>);}"><?php _e("Delete", "gfcnp")?></a>

                                            </span>

                                        </div>

                                    </td>

                                    

                                </tr>

                                <?php

                            }

                        }

                        else{

                            ?>

                            <tr>

                                <td colspan="4" style="padding:20px;">

                                    <?php echo sprintf(__("You don't have any Click & Pledge Forms configured. Let's go %screate one%s!", "gfcnp"), '<a href="admin.php?page=gfcnp&view=edit&id=0">', "</a>"); ?>

                                </td>

                            </tr>

                            <?php

                        }

                        ?>

                    </tbody>

                </table>
			</form>
		</div>
		
		<script>
		function DeleteSetting(id){

                jQuery("#action_argument").val(id);

                jQuery("#action").val("delete");

                jQuery("#feed_form")[0].submit();

            }
		</script>
		<?php
	}
	protected static function has_access($required_permission){

        $has_members_plugin = function_exists('members_get_capabilities');

        $has_access = $has_members_plugin ? current_user_can($required_permission) : current_user_can("level_7");

        if($has_access)

            return $has_members_plugin ? $required_permission : "level_7";

        else

            return false;

    }
}

if(!function_exists("rgget")){
		function rgget($name, $array=null){
			if(!isset($array))
				$array = $_GET;
			if(isset($array[$name]))
				return $array[$name];
			return "";
		}
	}
