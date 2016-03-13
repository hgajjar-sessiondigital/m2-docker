<?php

namespace Ktpl\Elasticsearch\Helper;

use Magento\Directory\Helper\Data as DirectoryData;
use Magento\Store\Model\ScopeInterface;
use Ktpl\Elasticsearch\Model\Resource\Client;

class Elasticsearch extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Text field types.
     *
     * @var array
     */
    protected $_textFieldTypes = array(
        'text',
        'varchar',
    );

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $productAttributeCollectionFactory;

    /**
     * Eav config
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * JSON helper
     */
    protected $jsonHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $productAttributeCollectionFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->eavConfig = $eavConfig;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    /**
     * Returns attribute field name (localized if needed).
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param string $localeCode
     * @return string
     */
    public function getAttributeFieldName($attribute, $localeCode = null)
    {
        $attributeCode = $attribute->getAttributeCode();
        // $backendType = $attribute->getBackendType();

        // if ($attributeCode != 'score' && in_array($backendType, $this->_textFieldTypes)) {
            // if (null === $localeCode) {
            //     $localeCode = $this->getLocaleCode();
            // }
            // $languageCode = $this->getLanguageCodeByLocaleCode($localeCode);
            // $languageSuffix = $languageCode ? '_' . $languageCode : '';
            // $attributeCode .= $languageSuffix;

        // }

        return $attributeCode;
    }

    /**
     * Checks if specified attribute is indexable by search engine.
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    public function _isAttributeIndexable($attribute)
    {
        if ($attribute->getBackendType() == 'varchar' && !$attribute->getBackendModel()) {
            return true;
        }

        if ($attribute->getBackendType() == 'int'
            && ($attribute->getIsSearchable() || $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch())
        ) {
            return true;
        }

        if ($attribute->getIsSearchable() || $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch()) {
            return true;
        }

        return false;
    }

    /**
     * Returns language code of specified locale code.
     *
     * @param string $localeCode
     * @return bool
     */
    public function getLanguageCodeByLocaleCode($localeCode)
    {
        $localeCode = (string) $localeCode;
        if (!$localeCode) {
            return false;
        }

        if (!isset($this->_languageCodes[$localeCode])) {
            $languages = $this->getSupportedLanguages();
            $this->_languageCodes[$localeCode] = false;
            foreach ($languages as $code => $locales) {
                if (is_array($locales)) {
                    if (in_array($localeCode, $locales)) {
                        $this->_languageCodes[$localeCode] = $code;
                    }
                } elseif ($localeCode == $locales) {
                    $this->_languageCodes[$localeCode] = $code;
                }
            }
        }

        return $this->_languageCodes[$localeCode];
    }

    /**
     * Returns store language code.
     *
     * @param mixed $store
     * @return bool
     */
    public function getLanguageCodeByStore($store = null)
    {
        return $this->getLanguageCodeByLocaleCode($this->getLocaleCode($store));
    }

    /**
     * Returns store locale code.
     *
     * @param null $store
     * @return string
     */
    public function getLocaleCode($store = null)
    {
        return $this->scopeConfig->getValue(
            DirectoryData::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }

    /**
     * Defines supported languages for snowball filter.
     *
     * @return array
     */
    public function getSupportedLanguages()
    {
        $default = array(
            /**
             * SnowBall filter based
             */
            // Danish
            'da' => 'da_DK',
            // Dutch
            'nl' => 'nl_NL',
            // English
            'en' => array('en_AU', 'en_CA', 'en_NZ', 'en_GB', 'en_US'),
            // Finnish
            'fi' => 'fi_FI',
            // French
            'fr' => array('fr_CA', 'fr_FR'),
            // German
            'de' => array('de_DE','de_DE','de_AT'),
            // Hungarian
            'hu' => 'hu_HU',
            // Italian
            'it' => array('it_IT','it_CH'),
            // Norwegian
            'nb' => array('nb_NO', 'nn_NO'),
            // Portuguese
            'pt' => array('pt_BR', 'pt_PT'),
            // Romanian
            'ro' => 'ro_RO',
            // Russian
            'ru' => 'ru_RU',
            // Spanish
            'es' => array('es_AR', 'es_CL', 'es_CO', 'es_CR', 'es_ES', 'es_MX', 'es_PA', 'es_PE', 'es_VE'),
            // Swedish
            'sv' => 'sv_SE',
            // Turkish
            'tr' => 'tr_TR',

            /**
             * Lucene class based
             */
            // Czech
            'cs' => 'cs_CZ',
            // Greek
            'el' => 'el_GR',
            // Thai
            'th' => 'th_TH',
            // Chinese
            'zh' => array('zh_CN', 'zh_HK', 'zh_TW'),
            // Japanese
            'ja' => 'ja_JP',
            // Korean
            'ko' => 'ko_KR'
        );

        return $default;
    }

    public function getClientUrl()
    {
        return $this->scopeConfig->getValue(Client::HOST_CONFIG_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieve searchable attributes
     *
     * @param string $backendType
     * @return \Magento\Eav\Model\Entity\Attribute[]
     */
    public function getSearchableAttributes($backendType = null)
    {
        $searchableAttributes = [];

        $productAttributes = $this->productAttributeCollectionFactory->create();
        $productAttributes->addToIndexFilter(true);

        /** @var \Magento\Eav\Model\Entity\Attribute[] $attributes */
        $attributes = $productAttributes->getItems();

        $entity = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getEntity();

        foreach ($attributes as $attribute) {
            if ($attribute->getIsFilterableInSearch()) {
                $attribute->setEntity($entity);
                $searchableAttributes[] = $attribute->getData();
            }
        }

        return $this->jsonHelper->jsonEncode($searchableAttributes);
    }
}
