<x-default-layout>
@section('title', __('Profile'))

    <div class="py-5">
        <div class="container mx-auto px-sm-4 px-lg-5 d-flex flex-column gap-4">
            <div class="p-4 p-sm-5 bg-white shadow rounded">
                <div class="col-12 col-xl-8">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 p-sm-5 bg-white shadow rounded">
                <div class="col-12 col-xl-8">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 p-sm-5 bg-white shadow rounded">
                <div class="col-12 col-xl-8">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
