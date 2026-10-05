<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    #[Route('/weather', name: 'weather_page', methods: ['GET'])]
    public function page(): Response
    {
        return $this->render('weather/index.html.twig', [
            'execution_mode' => getenv('DEMO_MODE') ?: 'inconnu',
        ]);
    }

    #[Route('/api/weather', name: 'api_weather', methods: ['GET'])]
    public function forecast(Request $request): JsonResponse
    {
        $city = trim((string) $request->query->get('city', 'Paris'));
        if (mb_strlen($city) < 2 || mb_strlen($city) > 80) {
            return $this->json(['error' => 'Le nom de la ville doit contenir entre 2 et 80 caractères.'], 422);
        }

        try {
            $geocoding = $this->httpClient->request('GET', 'https://geocoding-api.open-meteo.com/v1/search', [
                'query' => ['name' => $city, 'count' => 1, 'language' => 'fr', 'format' => 'json'],
                'timeout' => 8,
            ])->toArray();

            if (empty($geocoding['results'][0])) {
                return $this->json(['error' => "Aucune ville trouvée pour « {$city} »."], 404);
            }

            $place = $geocoding['results'][0];
            $weather = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude' => $place['latitude'],
                    'longitude' => $place['longitude'],
                    'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',
                    'hourly' => 'temperature_2m,precipitation_probability',
                    'forecast_hours' => 24,
                    'timezone' => 'auto',
                ],
                'timeout' => 8,
            ])->toArray();

            return $this->json([
                'location' => [
                    'name' => $place['name'],
                    'region' => $place['admin1'] ?? null,
                    'country' => $place['country'] ?? $place['country_code'],
                    'latitude' => $place['latitude'],
                    'longitude' => $place['longitude'],
                    'timezone' => $weather['timezone'],
                ],
                'current' => $weather['current'],
                'hourly' => [
                    'time' => $weather['hourly']['time'],
                    'temperature' => $weather['hourly']['temperature_2m'],
                    'precipitation_probability' => $weather['hourly']['precipitation_probability'],
                ],
                'source' => 'Open-Meteo',
                'fetched_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ]);
        } catch (\Throwable) {
            return $this->json(['error' => 'Le service météo externe est temporairement indisponible.'], 502);
        }
    }
}
