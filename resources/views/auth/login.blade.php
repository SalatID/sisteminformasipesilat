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
                    <label for="inputPassword">Password</label>
                    <input type="password" name="password"
                        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Password"
                        aria-describedby="inputPassword-error" aria-invalid="true">
                    @if ($errors->has('password'))
                        <span id="inputPassword-error"
                            class="error invalid-feedback">{{ $errors->first('password') }}</span>
                    @endif
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember">
                            <label for="remember">
                                Remember Me
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>

            {{-- <div class="social-auth-links text-center mb-3">
        <p>- OR -</p>
        <a href="#" class="btn btn-block btn-primary">
          <i class="fab fa-facebook mr-2"></i> Sign in using Facebook
        </a>
        <a href="#" class="btn btn-block btn-danger">
          <i class="fab fa-google-plus mr-2"></i> Sign in using Google+
        </a>
      </div> --}}
            <!-- /.social-auth-links -->

            <p class="mb-1">
                <a href="{{ route('forgot.password') }}">I forgot my password</a>
            </p>
            {{-- <p class="mb-0">
        <a href="register.html" class="text-center">Register a new membership</a>
      </p> --}}
        </div>
    </div>
@endsection
