<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarberServiceRequest;
use App\Http\Requests\UpdateBarberServiceRequest;
use App\Models\BarberService;

class BarberServiceController extends Controller
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
     * @param  \App\Http\Requests\StoreBarberServiceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBarberServiceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BarberService  $barberService
     * @return \Illuminate\Http\Response
     */
    public function show(BarberService $barberService)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BarberService  $barberService
     * @return \Illuminate\Http\Response
     */
    public function edit(BarberService $barberService)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBarberServiceRequest  $request
     * @param  \App\Models\BarberService  $barberService
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBarberServiceRequest $request, BarberService $barberService)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BarberService  $barberService
     * @return \Illuminate\Http\Response
     */
    public function destroy(BarberService $barberService)
    {
        //
    }
}
