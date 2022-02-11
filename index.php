<?php

function _print($v)
{
    echo '<pre>';
    print_r($v);
    echo '</pre>';
}

// Создание файла конфига
$configLocal = include 'config.local.php';
$userConfigFilePath = 'user-config.txt';

if (!file_exists($userConfigFilePath)) {
    file_put_contents($userConfigFilePath, '{}');
}

$userConfigTmp = file_get_contents($userConfigFilePath);
$userConfig = json_decode($userConfigTmp, true);

$input = file_get_contents("php://input");
$update = json_decode($input, true);

$chatId = $update["message"]["chat"]["id"];
$message = $update["message"]["text"];

$botApiKey = $configLocal['botApiKey'];
$botUrl = $path = "https://api.telegram.org/bot{$botApiKey}";

$keyboard = [
    [
        [
            'text' => 'Весёлый',
        ],
        [
            'text' => 'Грустный',
        ],
        [
            'text' => 'Злой',
        ],
    ],
    [
        [
            'text' => 'Спящий',
        ],
        [
            'text' => 'Танцующий',
        ],
        [
            'text' => 'Упоротый',
        ],
    ],
    [
        [
            'text' => 'GIF mode',
        ],
        [
            'text' => 'PIC mode',
        ],
    ],
];

$replyMarkup = json_encode([
    'keyboard' => $keyboard,
    'resize_keyboard' => true,
]);

$queryParams = [
    'chat_id' => $chatId,
    'reply_markup' => $replyMarkup,
];

switch ($message) {
    case '/start':
        $text = 'Какой вы сегодня коал?';
        $queryParams['text'] = $text;
        break;
    case 'GIF mode':
    case 'PIC mode':
        $messageParts = explode(' ', $message);
        $userConfig[$chatId]['mode'] = strtolower($messageParts[0]);
        file_put_contents($userConfigFilePath, json_encode($userConfig));
        $queryParams['text'] = "Теперь все коалкинсы будут в формате: {$userConfig[$chatId]['mode']}";
        break;
    default:
        $text = $message;
        $chips = "{$text} {$userConfig[$chatId]['mode']}";
        $imageApiQuery = urlencode("Коала {$chips}");
        $chips = urlencode($chips);
        $imageApiKey = $configLocal['serpApiKey'];

        // Find a photo
        $imageApiUrl = "https://serpapi.com/search.json?q={$imageApiQuery}&tbm=isch&ijn=0&api_key={$imageApiKey}";
        $imageApiResultTemp = file_get_contents($imageApiUrl);
        $imageApiResult = json_decode($imageApiResultTemp, true);

        //$randomImg = $imageApiResult['images_results'][rand(0, 50)]['original'];
        $randomImg = $imageApiResult['images_results'][rand(0, count($imageApiResult['images_results']) - 1)]['original'];

        $queryParams = [
            'chat_id' => $chatId,
            'reply_markup' => $replyMarkup,
        ];

        $tgMethod = 'sendPhoto';

        if (preg_match('/\.gif/', $randomImg)) {
            $tgMethod = 'sendAnimation';
            $queryParams['animation'] = $randomImg;
        } else {
            $queryParams['photo'] = $randomImg;
        }

        file_get_contents("{$botUrl}/{$tgMethod}?" . http_build_query($queryParams));
        exit();
}

file_get_contents("{$botUrl}/sendMessage?" . http_build_query($queryParams));
