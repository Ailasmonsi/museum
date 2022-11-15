<?php

class C_NGG_Pro_WPCLI_Pricelist
{
    /**
     * Create a new pricelist
     * @param array $args
     * @param array $assoc_args
     * @synopsis <pricelist_title> --author=<user_login>
     */
    function create($args, $assoc_args)
    {
        $mapper = C_Pricelist_Mapper::get_instance();
        $user   = get_user_by('login', $assoc_args['author']);

        if (!$user)
            WP_CLI::error("Unable to find user {$assoc_args['author']}");

        $pricelist = $mapper->create(array(
            'title'       => $args[0],
            'post_author' => $user->ID
        ));

        if ($pricelist && $pricelist->save())
        {
            $pricelist_id = $pricelist->id();
            WP_CLI::success("Created pricelist with id #{$pricelist_id}");
        }
        else {
            WP_CLI::error("Unable to create pricelist");
        }
    }

    /**
     * Deletes the requested pricelist
     * @param $args
     * @param $assoc_args
     * @synopsis <pricelist_id>
     */
    function delete($args, $assoc_args)
    {
        $mapper    = C_Pricelist_Mapper::get_instance();
        $pricelist = $mapper->find($args[0]);

        if (!$pricelist)
            WP_CLI::error("Unable to find pricelist {$args[0]}");

        $mapper->destroy($pricelist);
        WP_CLI::success("Pricelist with id #{$pricelist->ID} has been deleted");
    }

    /**
     * Change pricelist attributes
     * @param $args
     * @param $assoc_args
     * @synopsis <pricelist_id> [--title=<title>]
     */
    function edit($args, $assoc_args)
    {
        $mapper = C_Pricelist_Mapper::get_instance();
        $pricelist = $mapper->find($args[0]);

        if (!$pricelist)
            WP_CLI::error("Unable to find pricelist {$args[0]}");
        if (empty($assoc_args['title']))
            WP_CLI::error("You must provide a new title");

        $pricelist->title = $assoc_args['title'];
        $mapper->save($pricelist);

        WP_CLI::success("Pricelist with id #{$pricelist->ID} has been modified");
    }

    /**
     * List all pricelists
     * @param array $args
     * @param array $assoc_args
     * @subcommand list
     */
    function _list($args, $assoc_args)
    {
        $pricelist_mapper = C_Pricelist_Mapper::get_instance();
        $display = array();
        foreach ($pricelist_mapper->find_all(array(), TRUE) as $pricelist) {
            $author = get_user_by('ID', $pricelist->post_author);
            $display[] = array(
                'id'     => $pricelist->ID,
                'title'  => $pricelist->title,
                'items'  => count($pricelist->get_items()),
                'author' => $author->user_login
            );
        }

        \WP_CLI\Utils\format_items('table', $display, array('id', 'title', 'items', 'author'));
    }

    /**
     * @param $args
     * @param $assoc_args
     * @synopsis <pricelist_id>
     */
    function list_items($args, $assoc_args)
    {
        $pricelist_mapper = C_Pricelist_Mapper::get_instance();
        $category_manager = C_Pricelist_Category_Manager::get_instance();
        $source_manager   = C_Pricelist_Source_Manager::get_instance();

        $pricelist = $pricelist_mapper->find($args[0], TRUE);
        if (!$pricelist)
            WP_CLI::error("Unable to find pricelist {$args[0]}");

        $currency_key = C_NextGen_Settings::get_instance()->get('ecommerce_currency');

        $display = array();
        foreach ($pricelist->get_items() as $item) {
            $category  = $category_manager->get($item->category);
            $source    = $source_manager->get($item->source);
            $display[] = array(
                'id'       => $item->ID,
                'category' => $category['title'],
                'source'   => $source['title'],
                'cost'     => $item->cost === NULL ? '' : M_NextGen_Pro_Ecommerce::get_formatted_price($item->cost, $currency_key, FALSE),
                'price'    => M_NextGen_Pro_Ecommerce::get_formatted_price($item->price, $currency_key, FALSE),
                'title'    => $item->title
            );
        }

        \WP_CLI\Utils\format_items('table', $display, array('id', 'category', 'source', 'cost', 'price', 'title'));
    }

    /**
     * @param array $args
     * @param array $assoc_args
     */
    function list_categories($args, $assoc_args)
    {
        $manager = C_Pricelist_Category_Manager::get_instance();

        $display = array();
        foreach ($manager->get_ids() as $ndx => $category_id) {
            $category = $manager->get($category_id);
            $display[] = array(
                'id'   => $category_id,
                'name' => $category['title']
            );
        }

        \WP_CLI\Utils\format_items('table', $display, array('id', 'name'));
    }

    /**
     * @param array $args
     * @param array $assoc_args
     */
    function list_sources($args, $assoc_args)
    {
        $manager = C_Pricelist_Source_Manager::get_instance();

        $display = array();
        foreach ($manager->get_ids() as $ndx => $source_id) {
            $source = $manager->get($source_id);
            $display[] = array(
                'id'   => $source_id,
                'name' => $source['title']
            );
        }

        \WP_CLI\Utils\format_items('table', $display, array('id', 'name'));
    }
}

class C_NGG_Pro_WPCLI_Order
{
    /**
     * Deletes the requested order
     * @param $args
     * @param $assoc_args
     * @synopsis <order_id>
     */
    function delete($args, $assoc_args)
    {
        $mapper = C_Order_Mapper::get_instance();
        $order = $mapper->find_by_hash($args[0]);

        if (!$order)
            WP_CLI::error("Unable to find order {$args[0]}");

        $mapper->destroy($order);
        WP_CLI::success("Order with id #{$order->ID} has been deleted");
    }

    protected function format_amount($amount)
    {
        if ($amount === 0)
            return '';
        $currency_key = C_NextGen_Settings::get_instance()->get('ecommerce_currency');
        return M_NextGen_Pro_Ecommerce::get_formatted_price($amount, $currency_key, FALSE);
    }

    /**
     * List all orders
     * @param array $args
     * @param array $assoc_args
     * @subcommand list
     */
    function _list($args, $assoc_args)
    {
        $order_mapper = C_Order_Mapper::get_instance();
        $display = array();

        foreach ($order_mapper->find_all(array(), TRUE) as $order) {
            /** @var C_NextGen_Pro_Order $order */
            $cart = $order->get_cart();
            $coupon = $cart->get_coupon();

            $display[] = array(
                'hash' => $order->hash,
                'customer_name'   => $order->customer_name,
                'customer_email'  => $order->email,
                'payment_gateway' => $order->payment_gateway,
                'status'          => $order->status,
                'subtotal'        => $this->format_amount($order->subtotal),
                'shipping'        => $this->format_amount($order->shipping),
                'tax'             => $this->format_amount($order->tax),
                'total'           => $this->format_amount($order->total_amount),
                'coupon'          => $coupon ? $coupon['code'] : '',
                'date'            => $order->post_date
            );
        }

        \WP_CLI\Utils\format_items('table', $display, array(
            'hash',
            'customer_name',
            'customer_email',
            'payment_gateway',
            'status',
            'subtotal',
            'shipping',
            'tax',
            'total',
            'coupon',
            'date'
        ));
    }

}

WP_CLI::add_command('ngg order',     'C_NGG_Pro_WPCLI_Order');
WP_CLI::add_command('ngg pricelist', 'C_NGG_Pro_WPCLI_Pricelist');

