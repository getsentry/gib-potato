<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\EventFactory;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\Utility\Security;

class SlackController extends Controller
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        /**
         * Validate the Slack request
         *
         * @FIXME move into middleware
         * @see https://api.slack.com/authentication/verifying-requests-from-slack#verifying-requests-from-slack-using-signing-secrets__a-recipe-for-security__step-by-step-walk-through-for-validating-a-request
         */
        $messageTimeStamp = $this->request->getHeaderLine('X-Slack-Request-Timestamp');
        $messageTime = FrozenTime::createFromTimestampUTC((int)$messageTimeStamp);

        if ($messageTime <= new FrozenTime('5 minutes ago')) {
            return $this->response
                ->withStatus(403)
                ->withType('json')
                ->withStringBody(json_encode([
                    'message' => 'Forbidden',
                ]));
        }

        $signatureBaseString = sprintf('%s:%s:%s', 'v0', $messageTimeStamp, (string)$this->request->getBody());
        $gibpotatoSignature = 'v0=' . hash_hmac('sha256', $signatureBaseString, env('SLACK_SIGNING_SECRET'));

        $slackSignature = $this->request->getHeaderLine('X-Slack-Signature');

        if (Security::constantEquals($gibpotatoSignature, $slackSignature) === false) {
            return $this->response
                ->withStatus(403)
                ->withType('json')
                ->withStringBody(json_encode([
                    'message' => 'Forbidden',
                ]));
        }
    }

    public function index()
    {
        $type = $this->request->getData('type');

        switch ($type) {
            case 'url_verification':
                return $this->urlVerification();
            case 'event_callback':
                return $this->eventCallback();
            default:
                return $this->response
                    ->withType('json')
                    ->withStatus(200);
        }
    }

    protected function eventCallback()
    {
        $event = EventFactory::createEvent($this->request->getData('event'));
        $event->process();

        return $this->response
            ->withType('json')
            ->withStatus(200);
    }

    protected function urlVerification()
    {
        return $this->response
            ->withStringBody(json_encode([
                'challenge' => $this->request->getData('challenge'),
            ]))
            ->withType('json')
            ->withStatus(200);
    }
}
