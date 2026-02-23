@extends('layouts.main')

@section('content')

<h1>Contact</h1>

<form>
	<label>Name</label>
	<input type="text" placeholder="Your name">

	<label>Email</label>
	<input type="email" placeholder="Your email">

	<label>Message</label>
	<textarea placeholder="Your message"></textarea>

	<button type="submit">Send</button>
</form>

@endsection
