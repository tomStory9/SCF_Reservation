<?php

namespace App\Command;

use App\Repository\BookingRepository;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'email:reminder',
    description: '予約リマインドメールを送信します。',
)]
class EmailReminderCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private MailerService $mailerService,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('予約リマインドメール送信');
        $this->logger->info('email:reminder コマンドを開始しました。');

        $reminders = [
            ['+3 months', 'three_months', '3か月後'],
            ['+1 month', 'one_month', '1か月後'],
            ['+1 week', 'one_week', '1週間後'],
            ['+1 day', 'tomorrow', '明日'],
        ];

        $globalFound = 0;
        $globalSuccess = 0;
        $globalErrors = 0;

        try {
            foreach ($reminders as [$modifier, $reminderKey, $label]) {
                $bookings = $this->bookingRepository->findAllBookingInACertainTime($modifier);
                $total = count($bookings);

                $globalFound += $total;

                $io->section(sprintf('リマインド対象: %s', $label));
                $this->logger->info(sprintf('リマインド対象: %s', $label));

                if (0 === $total) {
                    $message = sprintf('%s の予約は見つかりませんでした。', $label);
                    $io->warning($message);
                    $this->logger->warning($message);
                    continue;
                }

                $io->text(sprintf('%s の予約が %d 件見つかりました。', $label, $total));
                $this->logger->info(sprintf('%s の予約が %d 件見つかりました。', $label, $total));

                foreach ($bookings as $booking) {
                    try {
                        $bookingId = $booking->getId();
                        $email = $booking->getUserBooking()?->getEmail();

                        $processingMessage = sprintf(
                            '予約 #%d%s のリマインドメールを処理中です。（%s）',
                            $bookingId,
                            $email ? sprintf(' (%s)', $email) : '',
                            $label
                        );

                        $io->text($processingMessage);
                        $this->logger->info($processingMessage);

                        $this->mailerService->sendReminderEmail($booking, $reminderKey);

                        ++$globalSuccess;

                        $successMessage = sprintf(
                            '予約 #%d%s のリマインドメールを送信しました。',
                            $bookingId,
                            $email ? sprintf(' (%s)', $email) : ''
                        );

                        $io->success($successMessage);
                        $this->logger->info($successMessage);
                    } catch (\Throwable $e) {
                        ++$globalErrors;

                        $bookingId = method_exists($booking, 'getId') ? $booking->getId() : 'unknown';

                        $errorMessage = sprintf(
                            '予約 #%s のリマインドメール送信に失敗しました: %s',
                            $bookingId,
                            $e->getMessage()
                        );

                        $io->error($errorMessage);
                        $this->logger->error($errorMessage, [
                            'booking_id' => $bookingId,
                            'exception' => $e,
                        ]);
                    }
                }
            }

            $io->section('実行結果');
            $io->listing([
                sprintf('対象予約数: %d', $globalFound),
                sprintf('送信成功数: %d', $globalSuccess),
                sprintf('エラー数: %d', $globalErrors),
            ]);

            $this->logger->info('email:reminder コマンドが終了しました。', [
                'total_found' => $globalFound,
                'success' => $globalSuccess,
                'errors' => $globalErrors,
            ]);

            if ($globalErrors > 0) {
                $io->warning('1件以上のエラーが発生しました。');

                return Command::FAILURE;
            }

            $io->success('すべてのリマインドメールを正常に送信しました。');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $criticalMessage = sprintf(
                'email:reminder コマンド実行中に重大なエラーが発生しました: %s',
                $e->getMessage()
            );

            $io->error($criticalMessage);
            $this->logger->critical($criticalMessage, [
                'exception' => $e,
            ]);

            return Command::FAILURE;
        }
    }
}
