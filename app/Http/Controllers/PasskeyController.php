<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Credential;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialSource;

class PasskeyController extends Controller
{

    public function authenticate(Request $request)
    {
        $data = $request->validate(['answer' => ['required', 'json']]);

        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->deserialize($data['answer'],PublicKeyCredential::class,'json');
        if(! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse){
            return to_route('profile.edit')->withFragment('managePasskeys');
        }
        $serializedPublicKeyCredential = json_decode($data['answer']);

        $passkey = Passkey::firstWhere('credential_id', $serializedPublicKeyCredential->rawId);

        if (! $passkey) {
            throw ValidationException::withMessages(['answer' => 'This passkey is not valid.']);
        }
        $csmFactory = new CeremonyStepManagerFactory;
        $requestCSM = $csmFactory->requestCeremony();

        try {
            $publicKeyCredentialSource = AuthenticatorAssertionResponseValidator::create($requestCSM)->check(
                publicKeyCredentialSource: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: $request->session()->get('passkey-authentication-options'),
                host: $request->getHost(),
                userHandle: null
            );
        } catch(Throwable $e){
            throw ValidationException::withMessages([
                'answer' => 'This passkey is not valid.'
            ]);
        }

        $serializedPublicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
        ->create()
        ->serialize($publicKeyCredentialSource,'json');

        $passkey->update(['data' => $serializedPublicKeyCredential]);

        Auth::loginUsingId($passkey->user_id);
        $request->session()->regenerate();
        return to_route('dashboard');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'passkey'=>['required','json']
        ]);

        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->deserialize($data['passkey'],PublicKeyCredential::class,'json');

        if(! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse){
            return to_route('login');
        }
        $csmFactory = new CeremonyStepManagerFactory;
        $creationCSM = $csmFactory->creationCeremony();

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCSM)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $request->session()->get('passkey-registration-options'),
                host: $request->getHost(),
            );
        } catch(Throwable $e){
            throw ValidationException::withMessages([
                'name'=>'The given passkey is invalid'
            ])->errorBag('createPasskey');
        }
        $serializedPublicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
        ->create()
        ->serialize($publicKeyCredentialSource,'json');

        $publicKeyCredentialSourceObject = json_decode($serializedPublicKeyCredential);

        $request->user()->passkeys()->create([
            'name'=>$data['name'],
            'credential_id'=>$publicKeyCredentialSourceObject->publicKeyCredentialId,
            'data'=>$serializedPublicKeyCredential
        ]);

        return to_route('profile.edit');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Passkey $passkey)
    {
        Gate::authorize('delete', $passkey);

        $passkey->delete();

        return to_route('profile.edit')->withFragment('managePasskeys');
    }
}
