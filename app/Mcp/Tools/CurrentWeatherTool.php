<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class CurrentWeatherTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Fetches real-time current weather for any location worldwide using the Open-Meteo API.
        Returns temperature, humidity, wind speed, precipitation, and weather conditions.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'units' => 'string|in:celsius,fahrenheit',
        ]);

        $location = $validated['location'];
        $units = $validated['units'] ?? 'celsius';

        $geocodeResponse = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
            'name' => $location,
            'count' => 1,
            'language' => 'en',
            'format' => 'json',
        ]);

        if ($geocodeResponse->failed() || empty($geocodeResponse->json('results'))) {
            return Response::make(Response::error("Could not find location: {$location}"));
        }

        $place = $geocodeResponse->json('results.0');
        $latitude = $place['latitude'];
        $longitude = $place['longitude'];
        $locationName = $place['name'];
        $country = $place['country'] ?? '';

        $weatherResponse = Http::get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,weather_code,wind_speed_10m,wind_direction_10m',
            'temperature_unit' => $units,
            'wind_speed_unit' => 'kmh',
            'timezone' => 'auto',
        ]);

        if ($weatherResponse->failed()) {
            return Response::make(Response::error('Failed to fetch weather data. Please try again.'));
        }

        $current = $weatherResponse->json('current');
        $unitLabel = $units === 'celsius' ? '°C' : '°F';
        $condition = $this->weatherCodeToDescription((int) $current['weather_code']);
        $temperature = $current['temperature_2m'];
        $feelsLike = $current['apparent_temperature'];
        $humidity = $current['relative_humidity_2m'];
        $windSpeed = $current['wind_speed_10m'];
        $windDirection = $this->degreesToCompass((float) $current['wind_direction_10m']);
        $precipitation = $current['precipitation'];

        $text = "Current weather in {$locationName}, {$country}:\n"
            ."- Condition: {$condition}\n"
            ."- Temperature: {$temperature}{$unitLabel} (feels like {$feelsLike}{$unitLabel})\n"
            ."- Humidity: {$humidity}%\n"
            ."- Wind: {$windSpeed} km/h {$windDirection}\n"
            ."- Precipitation: {$precipitation} mm";

        return Response::make(
            Response::text($text)
        )->withStructuredContent([
            'location' => "{$locationName}, {$country}",
            'condition' => $condition,
            'temperature' => $temperature,
            'feels_like' => $feelsLike,
            'units' => $unitLabel,
            'humidity' => $humidity,
            'wind_speed' => $windSpeed,
            'wind_direction' => $windDirection,
            'precipitation' => $precipitation,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()
                ->description('The city or location to get weather for (e.g. "London", "New York", "Tokyo").')
                ->required(),
            'units' => $schema->string()
                ->description('Temperature units.')
                ->enum(['celsius', 'fahrenheit'])
                ->default('celsius'),
        ];
    }

    private function weatherCodeToDescription(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear sky',
            $code === 1 => 'Mainly clear',
            $code === 2 => 'Partly cloudy',
            $code === 3 => 'Overcast',
            in_array($code, [45, 48]) => 'Fog',
            in_array($code, [51, 53, 55]) => 'Drizzle',
            in_array($code, [56, 57]) => 'Freezing drizzle',
            in_array($code, [61, 63, 65]) => 'Rain',
            in_array($code, [66, 67]) => 'Freezing rain',
            in_array($code, [71, 73, 75]) => 'Snowfall',
            $code === 77 => 'Snow grains',
            in_array($code, [80, 81, 82]) => 'Rain showers',
            in_array($code, [85, 86]) => 'Snow showers',
            $code === 95 => 'Thunderstorm',
            in_array($code, [96, 99]) => 'Thunderstorm with hail',
            default => 'Unknown',
        };
    }

    private function degreesToCompass(float $degrees): string
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        $index = (int) round($degrees / 45) % 8;

        return $directions[$index];
    }
}
