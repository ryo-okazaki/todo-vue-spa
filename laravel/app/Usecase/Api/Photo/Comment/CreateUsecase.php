<?php

declare(strict_types=1);

namespace App\Usecase\Api\Photo\Comment;

use App\Http\Payload;
use App\Http\Requests\Api\Photo\Comment\CreateRequest;
use App\Models\Photo;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Auth;

readonly class CreateUsecase
{
    public function __construct(private ConnectionInterface $connection) {}

    public function run(Photo $photo, CreateRequest $request): Payload
    {
        try {
            $this->connection->beginTransaction();

            $comments = $photo->comments()->create([
                'content' => $request->get('content'),
                'user_id' => Auth::id(),
            ]);

            $this->connection->commit();
        } catch (\Exception $e) {
            \Log::error($e);

            $this->connection->rollBack();

            return (new Payload())
                ->setStatus(Payload::FAILED);
        }

        $commentsWith = $comments->with(['author'])->get();

        return (new Payload())
            ->setOutput($commentsWith)
            ->setStatus(Payload::SUCCEED);
    }
}
