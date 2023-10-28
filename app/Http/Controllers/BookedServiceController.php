<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookedServiceRequest;
use App\Http\Requests\UpdateBookedServiceRequest;
use App\Models\BookedService;

class BookedServiceController extends Controller
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
     * @param  \App\Http\Requests\StoreBookedServiceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBookedServiceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BookedService  $bookedService
     * @return \Illuminate\Http\Response
     */
    public function show(BookedService $bookedService)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BookedService  $bookedService
     * @return \Illuminate\Http\Response
     */
    public function edit(BookedService $bookedService)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBookedServiceRequest  $request
     * @param  \App\Models\BookedService  $bookedService
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBookedServiceRequest $request, BookedService $bookedService)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BookedService  $bookedService
     * @return \Illuminate\Http\Response
     */
    public function destroy(BookedService $bookedService)
    {
        //
    }
}
