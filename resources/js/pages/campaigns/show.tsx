import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import CampaignInvitationController from '@/actions/App/Http/Controllers/CampaignInvitationController';
import CharacterController from '@/actions/App/Http/Controllers/CharacterController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ResourceIcon from '@/components/resource-icon';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/campaigns';
import { show as showCharacter } from '@/routes/campaigns/characters';
import { update as updateCampaignResource } from '@/routes/campaigns/resources';

type ResourceType = { value: string; label: string };

type CampaignResource = {
    id: number;
    resource_type: string;
    count: number;
};

type Character = {
    id: number;
    name: string;
    gold: number;
    experience: number;
    user: { id: number; name: string };
};

type Campaign = {
    id: number;
    name: string;
    resources: CampaignResource[];
    characters: Character[];
};

function ResourceCounter({
    campaign,
    resource,
}: {
    campaign: Campaign;
    resource: CampaignResource;
}) {
    function adjust(delta: number) {
        const newCount = Math.max(0, resource.count + delta);
        router.patch(
            updateCampaignResource.url({ campaign: campaign.id, resourceType: resource.resource_type }),
            { count: newCount },
            { preserveScroll: true },
        );
    }

    return (
        <div className="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => adjust(-1)}
                disabled={resource.count === 0}
                aria-label="Decrease"
            >
                −
            </Button>
            <span className="w-8 text-center text-lg font-semibold tabular-nums">{resource.count}</span>
            <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => adjust(1)}
                aria-label="Increase"
            >
                +
            </Button>
        </div>
    );
}

export default function CampaignShow({
    campaign,
    resourceTypes,
    isOwner,
    inviteLink,
    userHasCharacter,
}: {
    campaign: Campaign;
    resourceTypes: ResourceType[];
    isOwner: boolean;
    inviteLink: string | null;
    userHasCharacter: boolean;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: campaign.name, href: show.url(campaign) },
        ],
    });

    const resourceMap = Object.fromEntries(campaign.resources.map((r) => [r.resource_type, r]));

    return (
        <>
            <Head title={campaign.name} />

            <div className="space-y-10">
                <section>
                    <Heading title="Campaign pool" description="Shared resources available to all characters." />

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {resourceTypes.map((type) => {
                            const resource = resourceMap[type.value];

                            return (
                                <div
                                    key={type.value}
                                    className="flex flex-col gap-3 rounded-lg border border-border bg-card p-4"
                                >
                                    <div className="flex items-center gap-2">
                                        <ResourceIcon type={type.value} className="size-5 shrink-0 text-muted-foreground" />
                                        <span className="text-sm font-medium">{type.label}</span>
                                    </div>
                                    {resource && <ResourceCounter campaign={campaign} resource={resource} />}
                                </div>
                            );
                        })}
                    </div>
                </section>

                <section>
                    <Heading title="Characters" description="Each player's character in this campaign." />

                    <div className="space-y-2">
                        {campaign.characters.length > 0 ? (
                            campaign.characters.map((character) => (
                                <Link
                                    key={character.id}
                                    href={showCharacter.url({ campaign: campaign.id, character: character.id })}
                                    className="flex items-center justify-between rounded-lg border border-border bg-card px-4 py-3 transition-colors hover:bg-accent hover:text-accent-foreground"
                                >
                                    <span className="font-medium">{character.name}</span>
                                    <span className="text-sm text-muted-foreground">{character.user.name}</span>
                                </Link>
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">No characters yet. Add one below.</p>
                        )}
                    </div>
                </section>

                {isOwner && (
                    <section className="rounded-lg border border-border bg-card p-6">
                        <Heading variant="small" title="Invite players" description="Share this link to invite other players to this campaign." />

                        <div className="mt-4 space-y-3">
                            {inviteLink ? (
                                <div className="flex items-center gap-2">
                                    <Input readOnly value={inviteLink} className="font-mono text-sm" onClick={(e) => (e.target as HTMLInputElement).select()} />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => navigator.clipboard.writeText(inviteLink)}
                                    >
                                        Copy
                                    </Button>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">No invite link yet. Generate one below.</p>
                            )}

                            <Form {...CampaignInvitationController.store.form(campaign)}>
                                {({ processing }) => (
                                    <Button type="submit" variant="outline" disabled={processing}>
                                        {inviteLink ? 'Regenerate link' : 'Generate invite link'}
                                    </Button>
                                )}
                            </Form>
                        </div>
                    </section>
                )}

                {!userHasCharacter && (
                    <section className="rounded-lg border border-border bg-card p-6">
                        <Heading variant="small" title="Add character" description="Join this campaign with a new character." />

                        <Form
                            {...CharacterController.store.form(campaign)}
                            className="mt-4 space-y-4"
                            resetOnSuccess
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="character-name">Character name</Label>
                                        <Input id="character-name" name="name" placeholder="e.g. Quatryl Tinkerer" required />
                                        <InputError message={errors.name} />
                                    </div>

                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Adding…' : 'Add character'}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                )}
            </div>
        </>
    );
}

