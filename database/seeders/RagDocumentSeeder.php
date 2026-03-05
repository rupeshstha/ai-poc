<?php

namespace Database\Seeders;

use App\Models\RagDocument;
use App\Services\RagService;
use Illuminate\Database\Seeder;

class RagDocumentSeeder extends Seeder
{
    public function run(RagService $ragService): void
    {
        RagDocument::truncate();

        $documents = [
            [
                'title' => 'How El Niño Forms',
                'content' => 'El Niño is a climate pattern caused by the warming of surface waters in the central and eastern Pacific Ocean near the equator. Normally, trade winds blow westward, pushing warm water toward Asia and Australia. During El Niño, these trade winds weaken or reverse, allowing warm water to spread eastward toward South America. This disrupts normal weather patterns globally, causing droughts in Australia and heavy rainfall in South America.',
            ],
            [
                'title' => 'How Hurricanes Develop',
                'content' => 'Hurricanes form over warm ocean water, typically above 26°C. Warm, moist air rises from the ocean surface, creating an area of low pressure below. As more air rushes in to fill this low pressure, it warms, rises, and condenses into clouds, releasing heat that fuels further rising air. The Coriolis effect from Earth\'s rotation causes the system to spin. A hurricane is fully formed when winds reach 74 mph or higher.',
            ],
            [
                'title' => 'The Water Cycle Explained',
                'content' => 'The water cycle describes the continuous movement of water on Earth. Water evaporates from oceans, lakes, and rivers when heated by the sun. This water vapor rises into the atmosphere, cools, and condenses into clouds through condensation. When clouds collect enough water droplets, precipitation occurs as rain or snow. Water then flows back to oceans via rivers and streams or soaks into the ground as groundwater, completing the cycle.',
            ],
            [
                'title' => 'Why Leaves Change Color in Autumn',
                'content' => 'Leaves change color in autumn due to decreasing daylight and cooler temperatures. During summer, leaves are green because of chlorophyll, which absorbs sunlight for photosynthesis. As days shorten, trees stop producing chlorophyll, which breaks down, revealing yellow and orange pigments (carotenoids) that were always present. Red and purple pigments (anthocyanins) are newly produced as sugars trapped in leaves react to bright light. Eventually, the tree forms a separation layer that cuts off the leaf, which then falls.',
            ],
            [
                'title' => 'How Earthquakes Occur',
                'content' => 'Earthquakes occur when stress accumulated along geological faults is suddenly released. Earth\'s crust is made of tectonic plates that constantly move. Where plates meet, friction prevents smooth movement, causing stress buildup. When stress exceeds rock strength, plates slip suddenly, releasing seismic energy as waves that travel through the Earth. The point underground where rupture begins is called the hypocenter or focus. Directly above it on the surface is the epicenter, where shaking is usually strongest.',
            ],
            [
                'title' => 'How Black Holes Form',
                'content' => 'Black holes form when massive stars (at least 20 times the mass of our Sun) exhaust their nuclear fuel and collapse under their own gravity. During a star\'s life, outward pressure from nuclear fusion balances inward gravitational pull. When fusion stops, gravity wins — the core collapses in a fraction of a second, triggering a supernova explosion. If the remaining core is massive enough, it collapses into a singularity with gravity so intense that not even light can escape, creating a black hole.',
            ],
            [
                'title' => 'How Vaccines Work',
                'content' => 'Vaccines work by training the immune system to recognize and fight specific pathogens. A vaccine introduces a harmless version of a pathogen — weakened virus, dead virus, a piece of protein, or mRNA instructions — into the body. The immune system mounts a response, producing antibodies and memory cells. If the person later encounters the real pathogen, the immune system recognizes it quickly and destroys it before illness develops. This protection without actual infection is called immunization.',
            ],
            [
                'title' => 'Why the Ocean is Salty',
                'content' => 'The ocean is salty primarily because of rivers carrying dissolved minerals from rocks. Rainwater, slightly acidic, erodes rocks on land, dissolving minerals including salts. Rivers carry these dissolved minerals to the ocean. While water evaporates from the ocean (forming clouds), salts stay behind and accumulate over billions of years. Hydrothermal vents on the ocean floor also contribute minerals. The most common salt is sodium chloride, but seawater contains many other minerals too.',
            ],
            [
                'title' => 'How the Internet Works',
                'content' => 'The internet is a global network of interconnected computers that communicate using standardized protocols. Data is broken into packets, each labeled with source and destination addresses. These packets travel through routers — specialized devices that read addresses and forward packets along the fastest available path. Packets may take different routes and reassemble at the destination. The TCP/IP protocol suite governs addressing and transmission. Physical infrastructure includes fiber optic cables, undersea cables, and wireless towers.',
            ],
            [
                'title' => 'How Solar Panels Generate Electricity',
                'content' => 'Solar panels generate electricity through the photovoltaic effect. Each panel contains silicon cells that absorb photons from sunlight. When photons hit silicon atoms, they knock electrons loose, creating electron flow — an electric current. This current is direct current (DC). An inverter converts it to alternating current (AC) for home use. Multiple cells form a panel, and multiple panels form an array. Output depends on sunlight intensity, panel angle, and efficiency rating. Excess electricity can be stored in batteries or fed back to the grid.',
            ],
        ];

        foreach ($documents as $document) {
            RagDocument::create($document);
        }

        // Pre-compute TF-IDF vectors for all documents
        $ragService->indexDocuments();

        $this->command->info('Seeded ' . count($documents) . ' RAG documents and computed TF-IDF vectors.');
    }
}
