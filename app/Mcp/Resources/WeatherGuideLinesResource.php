<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class WeatherGuideLinesResource extends Resource
{
    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Guidelines and documentation for the Weather MCP server, including available data fields, units, and usage tips.
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $content = <<<'MARKDOWN'
            # Weather Server Guidelines

            ## Data Source
            Weather data is provided by [Open-Meteo](https://open-meteo.com), a free and open-source weather API with no API key required.

            ## Available Data Fields
            | Field | Description | Unit |
            |---|---|---|
            | `temperature` | Current air temperature | °C or °F |
            | `feels_like` | Apparent (feels like) temperature | °C or °F |
            | `humidity` | Relative humidity | % |
            | `wind_speed` | Wind speed | km/h |
            | `wind_direction` | Wind direction | Compass (N, NE, E…) |
            | `precipitation` | Current precipitation | mm |
            | `condition` | Human-readable weather description | — |

            ## Supported Units
            - **celsius** (default) — metric temperature
            - **fahrenheit** — imperial temperature

            ## Location Support
            Accepts any city name or location worldwide. Examples: "London", "New York", "Tokyo", "Mumbai", "Sydney".

            ## Weather Conditions
            Conditions are derived from WMO weather codes and include: Clear sky, Partly cloudy, Overcast, Fog, Drizzle, Rain, Snow, Thunderstorm, and more.

            ## Limitations
            - Data is refreshed every 15 minutes
            - Accuracy may vary for remote or rural locations
            - Uses the first geocoding result for ambiguous location names
        MARKDOWN;

        return Response::text($content);
    }

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('/weather-guidelines');
    }
}
