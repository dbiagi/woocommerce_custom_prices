<?php

namespace TWODigital\CustomPricing\API\Resource;

use WC_API_Resource;
use WC_API_Server;
use WP_Error;
use wpdb;

/**
 * CustomPricingResource class.
 *
 * @author Diego Viana <diego.viana@lecom.com.br>
 */
class CustomPricingResource extends WC_API_Resource {

    /**
     * @var string
     */
    protected $base = '/custom-pricing';

    /**
     * @var wpdb
     */
    private $wpdb;

    public function __construct(WC_API_Server $server) {
        global $wpdb;

        parent::__construct($server);

        $this->wpdb = $wpdb;
    }

    /**
     * Registra as rotas do webservice.
     * @param array $routes
     * @return array
     */
    public function register_routes($routes) {

        // GET/POST /custom-pricing
        $routes[$this->base] = array(
            array(array($this, 'getCustomPrices'), WC_API_Server::READABLE),
            array(array($this, 'setCustomPrices'), WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA),
        );

        // GET /custom-pricing/<id>
        $routes[$this->base . '/(?P<id>\d+)'] = array(
            array(array($this, 'getCustomPrices'), WC_API_Server::READABLE),
            array(array($this, 'deleteCustomPrices'), WC_API_Server::DELETABLE)
        );
        
        // GET /custom-pricing/<id>/<sku>
        $routes[$this->base . '/(?P<id>\d+)/(?P<sku>.+)'] = array(
            array(array($this, 'deleteCustomPrices'), WC_API_Server::DELETABLE),
        );

        return $routes;
    }

    /**
     * Retorna um array com os preços customizados
     * @param int $id O id do usuário.
     * @return array|WP_Error
     */
    public function getCustomPrices($id = 0) {

        $sql = <<<SQL
            SELECT 
                u.ID as 'user_id',
                u.display_name as 'user_name', 
                p.post_title as 'product_name', 
                c.price,
                c.sku
            FROM wp_custom_pricing c
            INNER JOIN wp_users u ON (u.ID = c.user_id)
            INNER JOIN wp_postmeta m ON (m.meta_key = '_sku' AND m.meta_value = c.sku)
            INNER JOIN wp_posts p ON (p.ID = m.post_id)
SQL;

        if ($id > 0) {
            $sql .= ' WHERE u.ID = ' . $id;
        }

        $results = $this->wpdb->get_results($sql);

        $users = array();

        foreach ($results as $data) {
            // Criar nó de usuário caso não existir
            if (!isset($users[$data->user_name])) {
                $users[$data->user_name] = array(
                    'id' => $data->user_id,
                    'products' => array()
                );
            }

            // Adiciona produto ao nó products do usuário
            $users[$data->user_name]['products'][] = array(
                'title' => $data->product_name,
                'sku' => $data->sku,
                'price' => $data->price
            );
        }

        return array(
            'custom_prices' => $users
        );
    }

    /**
     * Cria ou atualiza preços customizados.
     * @param type $data
     * @return WP_Error|array
     */
    public function setCustomPrices($data) {
        if (!isset($data['prices']) || !is_array($data['prices'])) {
            return new WP_Error(
                'extended_woocommerce_api_missing_pricing_data', __('Não foi informado os preços customizados.'), array('status' => 400)
            );
        }

        foreach ($data['prices'] as $price) {
            $data = array(
                'sku' => $price['sku'],
                'user_id' => $price['user_id'],
                'price' => $price['price']
            );

            $this->wpdb->replace('wp_custom_pricing', $data, array('%s', '%d', '%f'));
        }
        
        return $this->getCustomPrices();
    }
    
    /**
     * Apaga preços customizados.
     * @param int $id
     * @param string $sku
     * @return array|WP_Error
     */
    public function deleteCustomPrices($id, $sku = null){
        $where = array(
            'user_id' => $id
        );
        
        if($sku){
            $where['sku'] = $sku;
        }
        
        $this->wpdb->delete('wp_custom_pricing', $where, array('%d', '%s'));
        
        return $this->getCustomPrices();
    }

}
