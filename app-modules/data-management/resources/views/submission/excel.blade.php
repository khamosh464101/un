<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    @php
    if (!function_exists('getSurvey')) {
        function getSurvey(array $survey, string $name) {
            foreach ($survey as $item) {
                if ($item['name'] === $name) {
                    if($item['type'] === "select_one" || $item['type'] === "select_multiple") {
                        getLabel($item[''])
                    }
                    return $item;
                }
            }
            return null;
        }
    }
    @endphp
    @foreach($result as $key => $item) 
    {{getSurvey($item[$key])}}
    @endforeach
    <div>

    </div>
<p>{{ getSurvey('Ali') }}</p>
</body>
</html>