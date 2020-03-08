<?php

namespace Stof\DoctrineExtensionsBundle\Tests\EventListener;

use Gedmo\Blameable\BlameableListener;
use PHPUnit\Framework\TestCase;
use Stof\DoctrineExtensionsBundle\EventListener\BlameListener;
use Stof\DoctrineExtensionsBundle\Tests\Fixture\DecoupledUser;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author Antishov Viktor <antishov.viktor@gmail.com>
 * @package EventListener
 */
class BlameListenerTest extends TestCase
{
    /**
     * @var RequestEvent $event
     */
    private $event;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var BlameableListener
     */
    private $blameableListener;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var BlameListener
     */
    private $blameListener;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->blameableListener = $this->createMock(BlameableListener::class);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->event = $this->createMock(RequestEvent::class);

        $this->event->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->blameListener = new BlameListener(
            $this->blameableListener,
            $this->userProvider,
            $this->tokenStorage,
            $this->authorizationChecker
        );
    }

    public function testSubRequest(): void
    {
        /**
         * @var RequestEvent $event
         */
        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequestType'])
            ->getMock();

        $event->method('getRequestType')
            ->willReturn(HttpKernelInterface::SUB_REQUEST);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $this->blameListener->onKernelRequest($event);
    }

    /**
     * @dataProvider nullDependencyDataProvider
     * @param array $dependencies
     * @author Antishov Viktor <antishov.viktor@gmail.com>
     */
    public function testNullDependency(array $dependencies): void
    {
        $blameListener = new BlameListener($this->blameableListener, $this->userProvider, ...$dependencies);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $blameListener->onKernelRequest($this->event);
    }

    public function nullDependencyDataProvider(): array
    {
        return [
            'token_storage' => [[null, $this->authorizationChecker]],
            'authorization_checker' => [[$this->tokenStorage, null]],
        ];
    }

    public function testDecoupledUser(): void
    {
        $this->tokenStorage->method('getToken')
            ->willReturn($this->token);

        $this->authorizationChecker->method('isGranted')
            ->willReturn(true);

        $this->token->method('getUsername')
            ->willReturn('');

        $this->event->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->token->method('getUser')
            ->willReturn(new DecoupledUser());

        $user = $this->getMockBuilder(UserInterface::class)->getMock();

        $this->userProvider->method('loadUserByUsername')
            ->willReturn($user);

        $this->blameableListener->expects($this->once())
            ->method('setUserValue')
            ->with($user);

        $this->blameListener->onKernelRequest($this->event);
    }

    public function testGetUser(): void
    {
        $this->tokenStorage->method('getToken')
            ->willReturn($this->token);

        $this->authorizationChecker->method('isGranted')
            ->willReturn(true);

        $this->event->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $user = $this->getMockBuilder(UserInterface::class)->getMock();

        $this->token->method('getUser')
            ->willReturn($user);

        $this->blameableListener->expects($this->once())
            ->method('setUserValue')
            ->with($user);

        $this->blameListener->onKernelRequest($this->event);
    }

    public function testEmptyToken(): void
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken');

        $this->blameableListener->expects($this->never())
            ->method('setUserValue');

        $this->blameListener->onKernelRequest($this->event);
    }

    public function testNotGranted(): void
    {
        $this->tokenStorage->method('getToken')
            ->willReturn($this->token);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->blameableListener->expects($this->never())
            ->method('setUserValue');

        $this->blameListener->onKernelRequest($this->event);
    }
}