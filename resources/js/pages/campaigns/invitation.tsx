import { Form, Head } from '@inertiajs/react';
import CampaignInvitationController from '@/actions/App/Http/Controllers/CampaignInvitationController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/campaigns';

type Campaign = {
    id: number;
    name: string;
};

export default function CampaignInvitation({ campaign, token }: { campaign: Campaign; token: string }) {
    return (
        <>
            <Head title={`Join ${campaign.name}`} />

            <div className="flex flex-col items-center justify-center py-12">
                <div className="w-full max-w-sm space-y-6 rounded-lg border border-border bg-card p-8">
                    <Heading
                        title="You're invited!"
                        description={`Join the campaign "${campaign.name}" and start tracking your character.`}
                    />

                    <Form {...CampaignInvitationController.update.form(token)} className="space-y-4">
                        {({ processing }) => (
                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? 'Joining…' : 'Accept invitation'}
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

CampaignInvitation.layout = {
    breadcrumbs: [
        { title: 'Campaigns', href: index() },
        { title: 'Invitation' },
    ],
};
