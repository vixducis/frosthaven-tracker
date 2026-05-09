import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import CampaignInvitationController from '@/actions/App/Http/Controllers/CampaignInvitationController';
import CharacterController from '@/actions/App/Http/Controllers/CharacterController';
import ResourceTransferController from '@/actions/App/Http/Controllers/ResourceTransferController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ResourceIcon from '@/components/resource-icon';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/campaigns';
import { show as showCharacter } from '@/routes/campaigns/characters';
import { update as updateCampaignResource } from '@/routes/campaigns/resources';
import { update as updateCharacterResource } from '@/routes/campaigns/characters/resources';

type ResourceType = { value: string; label: string };

type CampaignResource = {
    id: number;
    resource_type: string;
    count: number;
};

type CharacterResource = {
    id: number;
    resource_type: string;
    count: number;
};

type Character = {
    id: number;
    name: string;
    gold: number;
    experience: number;
    retired_at: string | null;
    user: { id: number; name: string };
    resources: CharacterResource[];
};

type Campaign = {
    id: number;
    name: string;
    resources: CampaignResource[];
    characters: Character[];
};

function TransferDialog({
    campaign,
    character,
    resourceType,
    label,
    available,
}: {
    campaign: Campaign;
    character: Character;
    resourceType: string;
    label: string;
    available: number;
}) {
    const [open, setOpen] = useState(false);
    const [amount, setAmount] = useState(1);
    const [processing, setProcessing] = useState(false);

    function handleOpenChange(next: boolean) {
        setOpen(next);
        if (!next) {
            setAmount(1);
        }
    }

    function handleTransfer() {
        setProcessing(true);
        router.post(
            ResourceTransferController.store.url({ campaign: campaign.id, character: character.id }),
            { transfers: [{ resource_type: resourceType, amount }] },
            {
                preserveScroll: true,
                onSuccess: () => handleOpenChange(false),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="size-5 text-xs"
                    disabled={available === 0}
                    aria-label={`Transfer ${label} to campaign pool`}
                >
                    ↑
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-xs">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <ResourceIcon type={resourceType} className="size-4 shrink-0" />
                        Transfer {label}
                    </DialogTitle>
                    <DialogDescription>Move resources from your stash to the campaign pool.</DialogDescription>
                </DialogHeader>

                <div className="flex items-center justify-center gap-3 py-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        onClick={() => setAmount((a) => Math.max(1, a - 1))}
                        disabled={amount <= 1}
                        aria-label="Decrease"
                    >
                        −
                    </Button>
                    <span className="w-12 text-center text-2xl font-semibold tabular-nums">{amount}</span>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        onClick={() => setAmount((a) => Math.min(available, a + 1))}
                        disabled={amount >= available}
                        aria-label="Increase"
                    >
                        +
                    </Button>
                    <span className="text-sm text-muted-foreground">/ {available}</span>
                </div>

                <DialogFooter>
                    <Button type="button" onClick={handleTransfer} disabled={processing}>
                        {processing ? 'Transferring…' : 'Transfer'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

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
        <div className="flex items-center gap-1">
            <Button type="button" variant="outline" size="icon" className="size-5 text-xs" onClick={() => adjust(-1)} disabled={resource.count === 0} aria-label="Decrease">−</Button>
            <span className="w-6 text-center text-sm font-semibold tabular-nums">{resource.count}</span>
            <Button type="button" variant="outline" size="icon" className="size-5 text-xs" onClick={() => adjust(1)} aria-label="Increase">+</Button>
        </div>
    );
}

export default function CampaignShow({
    campaign,
    resourceTypes,
    isOwner,
    inviteLink,
    userHasCharacter,
    currentUserId,
}: {
    campaign: Campaign;
    resourceTypes: ResourceType[];
    isOwner: boolean;
    inviteLink: string | null;
    userHasCharacter: boolean;
    currentUserId: number;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: campaign.name, href: show.url(campaign) },
        ],
    });

    const resourceMap = Object.fromEntries(campaign.resources.map((r) => [r.resource_type, r]));
    const myCharacter = campaign.characters.find((c) => c.user.id === currentUserId) ?? null;
    const otherCharacters = campaign.characters.filter((c) => c.user.id !== currentUserId);

    return (
        <>
            <Head title={campaign.name} />

            <div className="space-y-10">
                {myCharacter && (
                    <section>
                        <Heading title={myCharacter.name} description="Your active character." />

                        <div className="rounded-lg border border-border bg-card p-4">
                            <div className="mb-3 space-y-2">
                                <div className="grid grid-cols-2 gap-2 sm:flex sm:gap-4">
                                    {([['gold', myCharacter.gold], ['experience', myCharacter.experience]] as const).map(([field, value]) => (
                                        <div key={field} className="flex items-center gap-1 text-sm">
                                            <span className="w-8 text-muted-foreground">{field === 'experience' ? 'XP' : 'Gold'}:</span>
                                            <Button type="button" variant="outline" size="icon" className="size-6"
                                                onClick={() => router.patch(
                                                    CharacterController.update.url({ campaign: campaign.id, character: myCharacter.id }),
                                                    { gold: field === 'gold' ? Math.max(0, value - 1) : myCharacter.gold, experience: field === 'experience' ? Math.max(0, value - 1) : myCharacter.experience },
                                                    { preserveScroll: true },
                                                )}
                                                disabled={value === 0}
                                                aria-label={`Decrease ${field}`}
                                            >−</Button>
                                            <span className="w-8 text-center font-semibold tabular-nums">{value}</span>
                                            <Button type="button" variant="outline" size="icon" className="size-6"
                                                onClick={() => router.patch(
                                                    CharacterController.update.url({ campaign: campaign.id, character: myCharacter.id }),
                                                    { gold: field === 'gold' ? value + 1 : myCharacter.gold, experience: field === 'experience' ? value + 1 : myCharacter.experience },
                                                    { preserveScroll: true },
                                                )}
                                                aria-label={`Increase ${field}`}
                                            >+</Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-2 sm:grid-cols-6">
                                {resourceTypes.map((type) => {
                                    const resource = Object.fromEntries(myCharacter.resources.map((r) => [r.resource_type, r]))[type.value];
                                    const count = resource?.count ?? 0;

                                    function adjust(delta: number) {
                                        router.patch(
                                            updateCharacterResource.url({
                                                campaign: campaign.id,
                                                character: myCharacter!.id,
                                                resourceType: type.value,
                                            }),
                                            { count: Math.max(0, count + delta) },
                                            { preserveScroll: true },
                                        );
                                    }

                                    return (
                                        <div key={type.value} className="flex flex-col items-center gap-1 rounded-md border border-border bg-background px-2 py-2 text-center">
                                            <ResourceIcon type={type.value} className="size-4 shrink-0 text-muted-foreground" />
                                            <span className="text-xs text-muted-foreground">{type.label}</span>
                                            <div className="flex items-center gap-1">
                                                <Button type="button" variant="outline" size="icon" className="size-5 text-xs" onClick={() => adjust(-1)} disabled={count === 0} aria-label="Decrease">−</Button>
                                                <span className="w-6 text-center text-sm font-semibold tabular-nums">{count}</span>
                                                <Button type="button" variant="outline" size="icon" className="size-5 text-xs" onClick={() => adjust(1)} aria-label="Increase">+</Button>
                                            </div>
                                            <TransferDialog
                                                campaign={campaign}
                                                character={myCharacter!}
                                                resourceType={type.value}
                                                label={type.label}
                                                available={count}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </section>
                )}

                <section>
                    <Heading title="Campaign pool" description="Shared resources available to all characters." />

                    <div className="grid grid-cols-3 gap-2 sm:grid-cols-6">
                        {resourceTypes.map((type) => {
                            const resource = resourceMap[type.value];

                            return (
                                <div key={type.value} className="flex flex-col items-center gap-1 rounded-md border border-border bg-background px-2 py-2 text-center">
                                    <ResourceIcon type={type.value} className="size-4 shrink-0 text-muted-foreground" />
                                    <span className="text-xs text-muted-foreground">{type.label}</span>
                                    {resource && <ResourceCounter campaign={campaign} resource={resource} />}
                                </div>
                            );
                        })}
                    </div>
                </section>

                {otherCharacters.length > 0 && (
                    <section>
                        <Heading title="Party" description="Other characters in this campaign." />

                        <div className="space-y-2">
                            {otherCharacters.map((character) => (
                                <Link
                                    key={character.id}
                                    href={showCharacter.url({ campaign: campaign.id, character: character.id })}
                                    className="flex items-center justify-between rounded-lg border border-border bg-card px-4 py-3 transition-colors hover:bg-accent hover:text-accent-foreground"
                                >
                                    <span className="font-medium">{character.name}</span>
                                    <span className="text-sm text-muted-foreground">{character.user.name}</span>
                                </Link>
                            ))}
                        </div>
                    </section>
                )}

                {isOwner && (
                    <section>
                        <Heading title="Invite players" description="Share this link to invite other players to this campaign." />

                        <div className="space-y-3">
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
                    <section>
                        <Heading title="Add character" description="Join this campaign with a new character." />

                        <Form
                            {...CharacterController.store.form(campaign)}
                            className="space-y-4"
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

