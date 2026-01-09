@extends('layout.index_auth')
@section('title', 'Forgot Password')
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
            <p class="login-box-msg">Forgot Password</p>

            <form action="{{ route('proc.forgot.password') }}" method="post">
                @csrf
                <div class="form-group">
                    <label for="inputEmail">Email</label>
                    <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        placeholder="Email" aria-describedby="inputEmail-error" aria-invalid="true" required>
                    @if ($errors->has('email'))
                        <span id="inputEmail-error" class="error invalid-feedback">{{ $errors->first('email') }}</span>
                    @endif
                </div>
                <div class="row">
                    <!-- /.col -->
                    <div class="col-12 justify-content-between">
                        <button type="submit" class="btn btn-success btn-block">Verify</button>
                        <p class="mb-1">
                            <a href="{{ route('login') }}">Login</a>
                        </p>
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


            {{-- <p class="mb-0">
        <a href="register.html" class="text-center">Register a new membership</a>
      </p> --}}
        </div>

    </div>
@endsection
