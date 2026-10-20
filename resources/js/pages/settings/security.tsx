import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { ContentCard } from '@/components/content-card';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { edit } from '@/routes/security';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';

// oxfmt-ignore
type Props = {
    passwordRules: string;
} &
    ManageTwoFactorProps;

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Security settings" />

            <h1 className="sr-only">Security settings</h1>

            <ContentCard
                title="Password Settings"
                description="Ensure your account is using a long, random password to stay secure."
            >
                <Form
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    onError={(errors) => {
                        if (errors.password) {
                            passwordInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPasswordInput.current?.focus();
                        }
                    }}
                    className="flex flex-col gap-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <Field data-invalid={!!errors.current_password}>
                                <FieldLabel htmlFor="current_password">
                                    Current password
                                </FieldLabel>

                                <PasswordInput
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    autoComplete="current-password"
                                    placeholder="Current password"
                                />

                                <FieldError>
                                    {errors.current_password}
                                </FieldError>
                            </Field>

                            <Field data-invalid={!!errors.password}>
                                <FieldLabel htmlFor="password">
                                    New password
                                </FieldLabel>

                                <PasswordInput
                                    id="password"
                                    ref={passwordInput}
                                    name="password"
                                    autoComplete="new-password"
                                    placeholder="New password"
                                    passwordrules={props.passwordRules}
                                />

                                <FieldError>{errors.password}</FieldError>
                            </Field>

                            <Field
                                data-invalid={!!errors.password_confirmation}
                            >
                                <FieldLabel htmlFor="password_confirmation">
                                    Confirm password
                                </FieldLabel>

                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    placeholder="Confirm password"
                                    passwordrules={props.passwordRules}
                                />

                                <FieldError>
                                    {errors.password_confirmation}
                                </FieldError>
                            </Field>

                            <div className="mt-4 flex justify-end gap-2">
                                <Button
                                    disabled={processing}
                                    data-test="update-password-button"
                                >
                                    {processing ? (
                                        <>
                                            <Spinner className="size-4" />
                                            Saving...
                                        </>
                                    ) : (
                                        'Save Changes'
                                    )}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </ContentCard>

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security settings',
            href: edit(),
        },
    ],
};
