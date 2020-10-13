<?php

namespace YOOtheme\Builder\Wordpress\Source\Type;

use YOOtheme\Str;
use YOOtheme\Url;

class AttachmentType
{
    /**
     * @return array
     */
    public static function config()
    {
        return [

            'fields' => [

                'url' => [
                    'type' => 'String',
                    'metadata' => [
                        'label' => 'Url',
                    ],
                    'extensions' => [
                        'call' => __CLASS__ . '::url',
                    ],
                ],

                'alt' => [
                    'type' => 'String',
                    'metadata' => [
                        'label' => 'Alt',
                    ],
                    'extensions' => [
                        'call' => __CLASS__ . '::alt',
                    ],
                ],

                'caption' => [
                    'type' => 'String',
                    'metadata' => [
                        'label' => 'Caption',
                    ],
                    'extensions' => [
                        'call' => __CLASS__ . '::caption',
                    ],
                ]

            ],

        ];
    }

    public static function caption($attachmentId)
    {
        return wp_get_attachment_caption($attachmentId);
    }

    public static function alt($attachmentId)
    {
        return get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
    }

    public static function url($attachmentId)
    {
        if (!$file = get_attached_file($attachmentId)) {
            return;
        }

        $url = URL::to($file);

        if (Str::startsWith($url, URL::base())) {
            $url = ltrim(substr($url, strlen(URL::base())), '/');
        }

        return $url;
    }
}
