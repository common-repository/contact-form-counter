<?php

//here calling the WP list table 
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
//listing class loading here
class MCF_WPCF7_Listings extends WP_List_Table
{
    function __construct()
    {
        parent::__construct(array(
            'singular' => 'post',
            'plural'   => 'posts',
            'ajax'     => false
        ));
    }
    //column-default
    function column_default($item, $column_name)
    {
        return '';
    }
    //column get
    function get_columns()
    {
        return array(
            "setting" => __('Contact Forms Lists', ContactFormCounter::DOMAIN)
        );
    }
    //column settings
    function column_setting($item)
    {
        $form_id = $item->ID;
        ob_start();
        ?>
        <div id="cfc_setting_<?php echo $form_id; ?>" class="clearfix">
            <h3><?php echo esc_html($item->post_title); ?></h3>
            <form method="post" action="options.php">
                <?php
                settings_fields('mcf_cf7_settings');
                do_settings_sections('mcf_cf7_settings');
                ?>
                <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Display type', ContactFormCounter::DOMAIN); ?></th>
                        <td><?php $this->type_field_callback(array('form_id' => $form_id)); ?></td>
                    </tr>
                    <tr data-form-id="<?php echo $form_id; ?>" data-field="start-count">
                        <th><?php _e('Start Count', ContactFormCounter::DOMAIN); ?></th>
                        <td><?php $this->count_field_callback(array('form_id' => $form_id)); ?></td>
                    </tr>
                    <tr data-form-id="<?php echo $form_id; ?>" data-field="digits">
                        <th><?php _e('No. of Digits', ContactFormCounter::DOMAIN); ?></th>
                        <td><?php $this->digits_field_callback(array('form_id' => $form_id)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Prefix', ContactFormCounter::DOMAIN); ?></th>
                        <td><?php $this->prefix_field_callback(array('form_id' => $form_id)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Mail Shortcode', ContactFormCounter::DOMAIN); ?></th>
                        <td><?php $this->mail_tag_field_callback(array('form_id' => $form_id)); ?></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', ContactFormCounter::DOMAIN), 'primary', 'submit_' . $form_id); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

   // Type field callback
function type_field_callback($args)
{
    $form_id = $args['form_id'];
    $value = get_option('mcf_cf7_type' . $form_id, 1);
    $option_name = 'mcf_cf7_settings';
    echo '<input type="radio" name="' . $option_name . '[type_' . $form_id . ']" value="1" ' . checked(1, $value, false) . ' />' . __('Serial Number', ContactFormCounter::DOMAIN) . '<br />';
    echo '<input type="radio" name="' . $option_name . '[type_' . $form_id . ']" value="2" ' . checked(2, $value, false) . ' />' . __('Timestamp (ms)', ContactFormCounter::DOMAIN) . '<br />';
    echo '<input type="radio" name="' . $option_name . '[type_' . $form_id . ']" value="3" ' . checked(3, $value, false) . ' />' . __('Date & Time', ContactFormCounter::DOMAIN);
}

// Count field callback
function count_field_callback($args)
{
    $form_id = $args['form_id'];
    $value = get_option('mcf_cf7_count' . $form_id, 0);
    $option_name = 'mcf_cf7_settings';
    echo '<input type="text" name="' . $option_name . '[count_' . $form_id . ']" value="' . esc_attr($value) . '" size="5" maxlength="4" />';
}

// Digit field callback
function digits_field_callback($args)
{
    $form_id = $args['form_id'];
    $value = get_option('mcf_cf7_digits' . $form_id, 0);
    $option_name = 'mcf_cf7_settings';
    echo '<input type="text" name="' . $option_name . '[digits_' . $form_id . ']" value="' . esc_attr($value) . '" size="2" maxlength="2" />';
}

// Prefix field callback
function prefix_field_callback($args)
{
    $form_id = $args['form_id'];
    $value = get_option('mcf_cf7_prefix' . $form_id, '');
    $option_name = 'mcf_cf7_settings';
    echo '<input type="text" name="' . $option_name . '[prefix_' . $form_id . ']" value="' . esc_attr($value) . '" size="15" maxlength="10" />';
}


    //mail-tag field
    function mail_tag_field_callback($args)
    {
        $form_id = $args['form_id'];
        $mail_tag = sprintf('[cfc_serial_number_%1$d]', $form_id);
        echo '<input type="text" class="spcf_class" readonly="readonly" value="' . esc_attr($mail_tag) . '" size="30" />';
        echo '<div class="mcf_tooltip"><span class="mcf_tooltiptext" id="myTooltip">Copy to clipboard</span> <button class="button button-secondary copy-btn" type="button">Copy!</button></div>';
    }
    //data prepare from list
    function prepare_items()
    {
        $posts_per_page = 9;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $args = array(
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'any',
            'posts_per_page' => $posts_per_page,
            'orderby' => 'title',
            'order' => 'ASC',
            'offset' => ($this->get_pagenum() - 1) * $posts_per_page
        );
        $this->items = get_posts($args);
        $total_items = wp_count_posts('wpcf7_contact_form')->publish;
        $total_pages = ceil($total_items / $posts_per_page);
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $posts_per_page,
            'total_pages' => $total_pages
        ));
    }
}
//end