<?php
/**
 * Copyright 2015 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * JsonStorage
 *
 * PHP version 5.3
 *
 * @category  Payment
 * @package   KlarnaAPI
 * @author    Klarna <support@klarna.com>
 * @copyright 2015 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      https://developers.klarna.com/
 */

/**
 * JSON storage class for KlarnaPClass
 *
 * This class is an JSON implementation of the PCStorage interface.
 *
 * @category  Payment
 * @package   KlarnaAPI
 * @author    Klarna <support@klarna.com>
 * @copyright 2015 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      https://developers.klarna.com/
 */
class JSONStorage extends PCStorage
{
    /**
     * Return the name of the storage type
     *
     * @return string
     */
    public function getName()
    {
        return "json";
    }

    /**
     * Checks if the file is writeable, readable or if the directory is.
     *
     * @param string $jsonFile json file that holds the pclasses
     *
     * @throws error
     * @return void
     */
    protected function checkURI($jsonFile)
    {
        //If file doesn't exist, check the directory.
        if (!file_exists($jsonFile)) {
            $jsonFile = dirname($jsonFile);
        }

        if (!is_writable($jsonFile)) {
            throw new Klarna_FileNotWritableException($jsonFile);
        }

        if (!is_readable($jsonFile)) {
            throw new Klarna_FileNotReadableException($jsonFile);
        }
    }

    /**
     * Clear the pclasses
     *
     * @param string $uri uri to file to clear
     *
     * @throws KlarnaException
     * @return void
     */
    public function clear($uri)
    {
        $this->checkURI($uri);
        unset($this->pclasses);
        if (file_exists($uri)) {
            unlink($uri);
        }
    }

    /**
     * Load pclasses from file
     *
     * @param string $uri uri to file to load
     *
     * @throws KlarnaException
     * @return void
     */
    public function load($uri)
    {
        $this->checkURI($uri);
        if (!file_exists($uri)) {
            //Do not fail, if file doesn't exist.
            return;
        }
        $arr = json_decode(file_get_contents($uri), true);

        if (count($arr) == 0) {
            return;
        }

        foreach ($arr as $pclasses) {
            if (count($pclasses) == 0) {
                continue;
            }
            foreach ($pclasses as $pclass) {
                $this->addPClass(new KlarnaPClass($pclass));
            }
        }
    }

    /**
     * Save pclasses to file
     *
     * @param string $uri uri to file to save
     *
     * @throws KlarnaException
     * @return void
     */
    public function save($uri)
    {
        try {
            $this->checkURI($uri);

            $output = array();
            foreach ($this->pclasses as $eid => $pclasses) {
                foreach ($pclasses as $pclass) {
                    if (!isset($output[$eid])) {
                        $output[$eid] = array();
                    }
                    $output[$eid][] = $pclass->toArray();
                }
            }
            if (count($this->pclasses) > 0) {
                file_put_contents($uri, json_encode($output));
            } else {
                file_put_contents($uri, "");
            }
        } catch(Exception $e) {
            throw new KlarnaException($e->getMessage());
        }
    }
}
