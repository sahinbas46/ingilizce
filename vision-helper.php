<?php
if (!function_exists('stockline_get_vision_metadata')) {
    function stockline_get_vision_metadata($image_url, $lang = 'en') {
        $api_key = get_option('stockline_vision_api_key', '');
        if (!$api_key || !$image_url) return [
            'title' => '',
            'desc' => '',
            'alt' => '',
            'keywords' => '',
        ];
        $img_data = @file_get_contents($image_url);
        if (!$img_data) return [
            'title' => '',
            'desc' => '',
            'alt' => '',
            'keywords' => '',
        ];
        $img_base64 = base64_encode($img_data);
        $payload = [
            "requests" => [[
                "image" => ["content" => $img_base64],
                "features" => [
                    ["type" => "LABEL_DETECTION", "maxResults" => 10],
                    ["type" => "WEB_DETECTION", "maxResults" => 5],
                    ["type" => "IMAGE_PROPERTIES", "maxResults" => 1]
                ]
            ]]
        ];
        $url = "https://vision.googleapis.com/v1/images:annotate?key=" . $api_key;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);
        if (
            !isset($result['responses'][0]) ||
            (empty($result['responses'][0]['labelAnnotations']) && empty($result['responses'][0]['webDetection']))
        ) {
            return [
                'title' => '',
                'desc' => '',
                'alt' => '',
                'keywords' => '',
            ];
        }
        $labels = [];
        if (!empty($result['responses'][0]['labelAnnotations'])) {
            foreach ($result['responses'][0]['labelAnnotations'] as $label) {
                $labels[] = $label['description'];
            }
        }
        if (!empty($result['responses'][0]['webDetection']['bestGuessLabels'])) {
            foreach ($result['responses'][0]['webDetection']['bestGuessLabels'] as $label) {
                $labels[] = $label['label'];
            }
        }
        $labels = array_unique($labels);
        $main_labels = array_slice($labels, 0, 7);

        $title = implode(' ', array_slice($main_labels, 0, 3));
        $title = trim(ucwords(strtolower($title)));

        $desc_sentences = [];
        if (!empty($labels)) {
            $desc_sentences[] = "This image includes " . implode(", ", array_slice($labels, 0, 3)) . ".";
            if (count($labels) > 3) {
                $desc_sentences[] = "You can also see " . implode(", ", array_slice($labels, 3, 3)) . ".";
            }
            $desc_sentences[] = "It is a high quality and SEO friendly image.";
            $desc_sentences[] = "Can be used in websites, blogs, or social media posts.";
        }
        $desc = implode(' ', array_slice($desc_sentences, 0, 4));
        $alt = implode(', ', array_slice($labels, 0, 4));
        $keywords = implode(', ', array_slice($labels, 0, 7));

        return [
            'title' => $title,
            'desc' => $desc,
            'alt' => $alt,
            'keywords' => $keywords,
        ];
    }
}