@extends('layout.index_auth')
@section('title', 'Login')
@section('content')
    <div class="card">

        <div class="card-body login-card-body">
            @if ($errors->has('error'))
                <div class="alert alert-{{ $errors->first('error') ? 'danger' : 'success' }} alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-{{ $errors->first('error') ? 'ban' : 'check' }}"></i>
                        {{ $errors->first('error') ? 'Error' : 'Sucess' }}</h5>
                    {{ $errors->first('message') }}
                </div>
            @endif
            <p class="login-box-msg">Sign in to start your session</p>

            <form action="{{ route('proc.login') }}" method="post">
                @csrf
                <div class="form-group">
                    <label for="inputEmail">Email address</label>
                    <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        placeholder="Enter email" aria-describedby="inputEmail-error" aria-invalid="true">
                    @if ($errors->has('email'))
                        <span id="inputEmail-error" class="error invalid-feedback">{{ $errors->first('email') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label for="inputRetypePassword">Retype Password</label>
                    <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control {{$errors->has('password')?'is-invalid':''}}" placeholder="Retype Password" aria-describedby="inputRetypePassword-error" aria-invalid="true">
                    <div class="input-group-append">
                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('password', 'togglePassword2')">
                        <i class="fas fa-eye" id="togglePassword2"></i>
                        </span>
                    </div>
                    @if($errors->has('password'))
                        <span id="inputRetypePassword-error" class="error invalid-feedback d-block">{{ $errors->first('password') }}</span>
                    @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <p class="mb-1">
                            <a href="{{ route('forgot.password') }}">I forgot my password</a>
                        </p>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>
        </div>
    </div>
@endsection
