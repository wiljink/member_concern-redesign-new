<!-- resources/views/thank-you.blade.php -->

@extends('layouts.app') <!-- Assuming you're using a layout named app.blade.php -->

@section('content')
    <div class="container mx-auto py-10">
        <div class="bg-white shadow-md rounded-lg p-6 text-center">
            <h1 class="text-2xl font-bold text-green-600 mb-4">Thank You!</h1>
            <p class="text-lg text-gray-700">
                Thank you for posting your concern. A branch representative will contact you soon.
            </p>
            <div class="mt-6">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-700 underline">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
@endsection
