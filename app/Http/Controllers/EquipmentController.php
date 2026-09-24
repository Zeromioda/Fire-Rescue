<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Apparatus;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = Equipment::latest()->get();
        $apparatuses = Apparatus::latest()->get();

        $stats = [
            'total_items' => $equipment->sum('quantity') + $apparatuses->count(),
            'available' => $equipment->whereIn('status', ['Available', 'Serviceable'])->sum('quantity') 
                + $apparatuses->whereIn('status', ['Available', 'In Service', 'available'])->count(),
            'in_maintenance' => $equipment->whereIn('status', ['In Maintenance', 'Maintenance'])->sum('quantity') 
                + $apparatuses->whereIn('status', ['Maintenance', 'Under Maintenance', 'maintenance'])->count(),
            'damaged' => $equipment->whereIn('status', ['Damaged', 'Expired', 'Decommissioned'])->sum('quantity') 
                + $apparatuses->whereIn('status', ['Out of Service', 'Damaged', 'Decommissioned'])->count(),
        ];

        return view('equipment.index', compact('equipment', 'apparatuses', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|string',
            'status'   => 'required|in:Available,Assigned,In Maintenance,Decommissioned',
            'quantity' => 'required|integer|min:1',
        ]);

        // Auto-generate unique asset tag
        $validated['asset_tag'] = 'PPE-' . rand(100, 999) . '-' . sprintf('%03d', rand(1, 999));

        Equipment::create($validated);

        return redirect()->back()->with('status', 'Equipment added successfully!');
    }

    public function storeApparatus(Request $request)
    {
        $validated = $request->validate([
            'call_sign'          => 'required|string|max:255',
            'plate_number'       => 'required|string|max:255|unique:apparatuses,plate_number',
            'type'               => 'required|string',
            'status'             => 'required|string',
            'fuel_level_percent' => 'required|numeric|min:0|max:100',
        ]);

        Apparatus::create($validated);

        return redirect()->back()->with('status', 'Apparatus registered successfully!');
    }

    // Update Equipment (Admin edits all details; Firefighter edits status)
    public function update(Request $request, Equipment $equipment)
    {
        $allowedStatuses = ['Available', 'Assigned', 'In Maintenance'];

        // Decommission status is reserved for Admin users
        if (auth()->user()->hasRole('Admin')) {
            $allowedStatuses[] = 'Decommissioned';
        }

        $rules = [
            'status' => 'required|in:' . implode(',', $allowedStatuses),
        ];

        // Include Admin metadata validation when present in request
        if (auth()->user()->hasRole('Admin')) {
            $rules['asset_tag'] = 'sometimes|required|string';
            $rules['name']      = 'sometimes|required|string|max:255';
            $rules['category']  = 'sometimes|required|string';
            $rules['quantity']  = 'sometimes|required|integer|min:0';
        }

        $validated = $request->validate($rules);
        $equipment->update($validated);

        return redirect()->back()->with('status', 'Equipment details updated successfully!');
    }

    // Update Apparatus (Both roles update fuel & status; Admin edits call sign & plate number)
    public function updateApparatus(Request $request, Apparatus $apparatus)
    {
        $rules = [
            'status'             => 'required|string',
            'fuel_level_percent' => 'sometimes|required|numeric|min:0|max:100',
        ];

        // Include Admin metadata validation when present in request
        if (auth()->user()->hasRole('Admin')) {
            $rules['call_sign']    = 'sometimes|required|string|max:255';
            $rules['plate_number'] = 'sometimes|required|string|max:255|unique:apparatuses,plate_number,' . $apparatus->id;
        }

        $validated = $request->validate($rules);
        $apparatus->update($validated);

        return redirect()->back()->with('status', 'Apparatus details updated successfully!');
    }

    // Admin-only delete methods
    public function destroy(Equipment $equipment)
    {
        $equipment->delete();
        return redirect()->back()->with('status', 'Equipment removed!');
    }

    public function destroyApparatus(Apparatus $apparatus)
    {
        $apparatus->delete();
        return redirect()->back()->with('status', 'Apparatus removed!');
    }
}