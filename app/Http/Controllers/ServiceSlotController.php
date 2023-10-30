<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceSlotRequest;
use App\Http\Requests\UpdateServiceSlotRequest;
use App\Models\ServiceSlot;

class ServiceSlotController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreServiceSlotRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreServiceSlotRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ServiceSlot  $serviceSlot
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceSlot $serviceSlot)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ServiceSlot  $serviceSlot
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceSlot $serviceSlot)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateServiceSlotRequest  $request
     * @param  \App\Models\ServiceSlot  $serviceSlot
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateServiceSlotRequest $request, ServiceSlot $serviceSlot)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceSlot  $serviceSlot
     * @return \Illuminate\Http\Response
     */
    public function destroy(ServiceSlot $serviceSlot)
    {
        //
    }
}
