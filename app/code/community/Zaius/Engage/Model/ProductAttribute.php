<?php

class Zaius_Engage_Model_ProductAttribute
{

    private static $PRODUCT_ATTRIBUTES_TO_IGNORE = array(
        'entity_id', 'attribute_set_id', 'type_id',
        'entity_type_id', 'category_ids', 'required_options',
        'has_options', 'created_at', 'updated_at', 'media_gallery',
        'image', 'small_image', 'thumbnail'
    );

    public static function getAttributes($product)
    {
        $entity = array();
        foreach ($product->getAttributes() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $value = $attribute->getFrontend()->getValue($product);
            if (!in_array($attributeCode, self::$PRODUCT_ATTRIBUTES_TO_IGNORE) && !empty($value)) {
                $entity[$attributeCode] = $value;
            }
        }
        return $entity;
    }
}
