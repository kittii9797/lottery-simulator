<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Result;
use App\Models\DrawnNumber;
use Carbon\Carbon;

class LottoController extends Controller
{
    public function drawNumbers()
    {
        $numbers = range(1, 90);
        shuffle($numbers);
        $drawnNumbers = array_slice($numbers, 0, 5);

        DrawnNumber::create([
            'numbers' => json_encode($drawnNumbers),
        ]);

        return response()->json($drawnNumbers);
    }

    public function getAllDraws()
    {
        $draws = DrawnNumber::all();

        $draws = $draws->map(function ($draw) {
            return [
                'id' => $draw->id,
                'numbers' => json_decode($draw->numbers, true),
                'created_at' => $draw->created_at
            ];
        });

        return response()->json($draws);
    }

    public function storeTicket(Request $request)
    {
        $validated = $request->validate([
            'numbers' => 'required|array|min:5|max:5',
            'numbers.*' => 'integer|between:1,90',
        ]);

        $ticket = Ticket::create([
            'numbers' => json_encode($validated['numbers']),
            'created_at' => Carbon::now(),
        ]);

        return response()->json($ticket, 201);
    }
    
    public function generateResults()
    {
        $tickets = Ticket::all();
        $drawCount = 0;
        $foundFiveMatches = false;
        $winningTickets = [];

        while (!$foundFiveMatches && $drawCount < 100) {
            $drawCount++;
            $drawResponse = $this->drawNumbers(); // Draw numbers and get the response
            $drawnNumbers = json_decode($drawResponse->getContent(), true); // Decode JSON response

            foreach ($tickets as $ticket) {
                $ticketNumbers = json_decode($ticket->numbers, true);
                $matches = count(array_intersect($drawnNumbers, $ticketNumbers));

                Result::create([
                    'ticket_id' => $ticket->id,
                    'matches' => $matches
                ]);

                if ($matches === 5) {
                    $foundFiveMatches = true;
                    $winningTickets[] = $ticket;
                    break;
                }
            }
        }

        return response()->json([
            'message' => $foundFiveMatches ? 'A ticket with 5 matches has been found.' : '100 draws completed without finding a ticket with 5 matches.',
            'draws' => $drawCount,
            'foundFiveMatches' => $foundFiveMatches,
            'winningTickets' => $winningTickets 
        ]);
    }



    public function resetGame()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Ticket::truncate();
        Result::truncate();
        DrawnNumber::truncate();
        
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        return response()->json(['message' => 'Game reset successfully.'], 200);
    }

    public function getLastResult()
    {
        $lastDrawnNumber = DrawnNumber::latest()->first();
    
        if (!$lastDrawnNumber) {
            return response()->json(['message' => 'No drawn numbers found'], 404);
        }
    
        $drawnNumbers = json_decode($lastDrawnNumber->numbers);
    
        return response()->json([
            'numbers' => $drawnNumbers
        ]);
    }

    public function getStatistics()
    {
        $totalTickets = Ticket::count();
        $totalResults = drawnNumber::count();
        $totalCost = $totalTickets * 400;

        $results = Result::selectRaw('matches, count(*) as count')
            ->groupBy('matches')
            ->get();

        return response()->json([
            'totalTickets' => $totalTickets,
            'totalCost' => $totalCost,
            'totalResults' => $totalResults,
            'years' => 0,
            'results' => $results,
        ]);
    }

}
