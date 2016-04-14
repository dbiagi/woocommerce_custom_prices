<?php

namespace TWODigital\CustomPricing\Plugin;

use TWODigital\CustomPricing\API\Resource\CustomPricingResource;
use wpdb;
use WC_Product;

/**
 * CustomPricingPlugin class.
 *
 * @author Diego Viana <diego.viana@lecom.com.br>
 */
class CustomPricingPlugin {

    /**
     * Variável global de acesso ao banco do wordpress.
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Nome da tabela de controle de preços personalizados no banco de dados.
     * @var string
     */
    private $table = 'wp_custom_pricing';

    public function __construct() {
        global $wpdb;

        $this->wpdb = $wpdb;

        $this->initialize();

        add_filter('woocommerce_get_price', array($this, 'custom_price'), 10, 2);
        
        add_filter('woocommerce_api_classes', array($this, 'addResourceClasses'), 10, 1);
    }
    
    /**
     * Retorna um array de classes filhas WC_API_Resource para extender a API do woocommerce.
     * @return array
     */
    private function getResources() {
        return array(
            CustomPricingResource::class
        );
    }
    
    /**
     * Inicia o plugin caso seja a primeira execução.
     * @return void
     */
    private function initialize() {
        
        $checkSql = <<<SQL
        SELECT 1 as exist
        FROM information_schema.`TABLES` t
        WHERE t.TABLE_NAME = '{$this->table}'
SQL;

        $exists = $this->wpdb->get_col($checkSql);

        // Verifica se a tabela já existe.
        if ($exists) {
            return;
        }

        $createSql = <<<SQL
        CREATE TABLE `{$this->table}` (
            `sku` VARCHAR(255) NOT NULL,
            `user_id` BIGINT(20) UNSIGNED NOT NULL,
            `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            PRIMARY KEY (`sku`, `user_id`),
            INDEX `FK_{$this->table}_wp_users` (`user_id`),
            INDEX `FK_{$this->table}_wp_posts` (`sku`),
            CONSTRAINT `FK_{$this->table}_wp_users` FOREIGN KEY (`user_id`) REFERENCES `wp_users` (`ID`)
        )
        ENGINE=InnoDB
SQL;

            
        // Cria tabela do plugin caso não existir.
        $initialized = $this->wpdb->query($createSql);

        if (!$initialized) {
            add_action('admin_notices', array($this, 'init_error'), 20);
        }
    }
    
    /**
     * Adiciona as classes ao array de resources da API do woocommerce.
     * @param array $array
     * @return array
     */
    public function addResourceClasses($array){
        return array_merge($array, $this->getResources());
    }

    /**
     * Filtro de preço.
     * @param float $price
     * @param WC_Product $product
     * @return float
     */
    public function custom_price($price, $product) {
        $userId = get_current_user_id();

        if ($userId <= 0 || !$product->get_sku()) {
            return $price;
        }
        
        return $this->get_custom_price($userId, $product->get_sku()) ?: $price;
    }

    /**
     * Pega preço diferenciado cadastrado no banco.
     * @param int $userId
     * @param int $sku
     * @return float|null
     */
    public function get_custom_price($userId, $sku) {
        $sql = sprintf(
            'SELECT price 
        FROM %s
        WHERE user_id = %s
        AND sku = \'%s\'', $this->table, $userId, $sku
        );
        
        return $this->wpdb->get_var($sql);
    }

    /**
     * Printa mensagem de erro na tela do admin do wordpress.
     */
    public function init_error() {
        $msg = 'Erro ao ativar o plugin <strong>Custom Pricing</strong>.';
        $html = <<<HTML
        <div class="updated error is-dismissible">
            <p>$msg</p>
        </div>
HTML;

        echo $html;
    }
}
