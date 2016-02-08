<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Addons\YandexTank;

use Tygh\Database\Connection;

class AmmoFileGenerator
{
    protected $db_connection;

    protected $lang_code_list;

    protected $server_http_host;

    public function __construct(Connection $db, array $lang_code_list, $server_http_host)
    {
        $this->db_connection = $db;
        $this->lang_code_list = $lang_code_list;
        $this->server_http_host = $server_http_host;
    }

    protected function getRequestTypes()
    {
        return array(
            'authenticated' => array(
                'weight' => 5, // Only 5% of all requests are made with authenticated users
                'headers_callback' => function (\Faker\Generator $faker, &$headers_list) {
                    $headers_list['Cookies'] = "fpc_sid_customer_d1caa={$faker->sha1}-1-C; logged_in=Y";
                },
            ),
            'with_session' => array(
                'weight' => 5, // Only 5% of all requests are made with non-authenticated users with session started
                'headers_callback' => function (\Faker\Generator $faker, &$headers_list) {
                    $headers_list['Cookies'] = "fpc_sid_customer_d1caa={$faker->sha1}-1-C";
                },
            ),
            'guest' => array(
                'weight' => 90, // 90% of all requests are made with non-authenticated users without session started
                'headers_callback' => function (\Faker\Generator $faker, &$headers_list) {
                    $headers_list['Cookies'] = 'None';
                },
            ),
        );
    }

    protected function getUrlSources()
    {
        return array(
            array(
                'sql' => 'SELECT `product_id` FROM ?:products WHERE `status` = "A" LIMIT ?i OFFSET ?i',
                'url_prefix' => 'products.view?product_id=',
                'primary_key' => 'product_id',
            ),
            array(
                'sql' => 'SELECT `category_id` FROM ?:categories WHERE `status` = "A" LIMIT ?i OFFSET ?i',
                'url_prefix' => 'categories.view?category_id=',
                'primary_key' => 'category_id',
            ),
        );
    }

    public function generate($output_file_path)
    {
        $urls_tmp_file_handle = $this->generateUrls();
        $output_file_handle = fopen($output_file_path, 'w+');

        list($request_type_weights, $request_type_names) = $this->getRequestTypeWeights();

        $generated_requests_counter = 0;

        fseek($urls_tmp_file_handle, 0);

        while (!feof($urls_tmp_file_handle)) {
            $request_url_path = trim(fgets($urls_tmp_file_handle, 4096));

            if (empty($request_url_path)) {
                continue;
            }

            $request_type = $this->getWeighedRandomItem($request_type_names, $request_type_weights);

            fwrite($output_file_handle, $this->generateAmmo($request_type, trim($request_url_path)));

            $generated_requests_counter++;
        }

        fclose($output_file_handle);
        fclose($urls_tmp_file_handle);
    }

    protected function generateUrls()
    {
        $urls_tmp_file_handle = tmpfile();

        foreach ($this->getUrlSources() as $url_source) {
            $this->generateUrlsFromUrlSource($url_source, $urls_tmp_file_handle);
        }

        return $urls_tmp_file_handle;
    }

    protected function generateUrlsFromUrlSource(array $url_source, $urls_tmp_file_handle)
    {
        $page_size = 100;
        $offset = 0;

        while (sizeof($url_items = $this->db_connection->getSingleHash($url_source['sql'], array($url_source['primary_key'], $url_source['primary_key']), $page_size, $offset)) != 0) {

            foreach ($url_items as $item) {

                foreach ($this->lang_code_list as $lang_code) {
                    $url = fn_url($url_source['url_prefix'] . $item, 'C', 'rel', $lang_code);

                    $url = '/' . ltrim($url, '\\/');

                    fwrite($urls_tmp_file_handle, $url . PHP_EOL);
                }
            }

            $offset += $page_size;
        }
    }


    protected function getRequestTypeWeights()
    {
        $weights = array();

        $names = array();

        foreach ($this->getRequestTypes() as $request_type_name => $request_type_config) {
            $weights[] = $request_type_config['weight'];
            $names[] = $request_type_name;
        }

        return array($weights, $names);
    }

    /**
     * Pick a random item based on weights.
     *
     * @see http://w-shadow.com/blog/2008/12/10/fast-weighted-random-choice-in-php/
     *
     * @param array $values  Array of elements to choose from
     * @param array $weights An array of weights. Weight must be a positive number.
     *
     * @return mixed Selected element.
     */
    protected function getWeighedRandomItem($values, $weights)
    {
        $count = count($values);
        $i = 0;
        $n = 0;
        $num = mt_rand(0, array_sum($weights));
        while ($i < $count) {
            $n += $weights[$i];
            if ($n >= $num) {
                break;
            }
            $i++;
        }

        return $values[$i];
    }

    protected function generateAmmo($request_type, $request_http_path)
    {
        $faker = \Faker\Factory::create();


        $browser_list = array(
            'chrome',
            'firefox',
            'safari',
            'opera',
            'internetExplorer'
        );
        $ua_method = $browser_list[array_rand($browser_list)];

        $headers_list = array(
            'Connection' => 'close',
            'Host' => $this->server_http_host,
            'User-Agent' => $faker->{$ua_method},
            'Accept-Encoding' => 'gzip, deflate, sdch',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,de;q=0.2,fr;q=0.2,nl;q=0.2',
            'Cookies' => 'None'
        );

        if ((mt_rand(1, 10) % 3) == 0) {
            $headers_list['Referer'] = $faker->url;
        }

        if ((mt_rand(1, 15) % 4) == 0) {
            $headers_list['Accept-Language'] = "{$faker->locale};q=0.8,{$faker->locale};q=0.6";
        }

        $request_type_config = $this->getRequestTypes();
        $request_type_config = $request_type_config[$request_type];

        if (isset($request_type_config['headers_callback']) && is_callable($request_type_config['headers_callback'])) {
            $request_type_config['headers_callback']($faker, $headers_list);
        }

        $ammo = '';

        foreach ($headers_list as $header_name => $header_value) {
            $ammo .= "[{$header_name}: {$header_value}]" . PHP_EOL;
        }

        $ammo .= "{$request_http_path} {$request_type}" . PHP_EOL;

        return $ammo;
    }
}
